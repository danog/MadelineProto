<?php

declare(strict_types=1);

/**
 * EventHandler module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\DeferredFuture;
use Amp\Future;
use Amp\Sync\LocalMutex;
use AssertionError;
use Closure;
use danog\Loop\PeriodicLoop;
use danog\MadelineProto\Db\DbPropertiesTrait;
use danog\MadelineProto\EventHandler\Attributes\Cron;
use danog\MadelineProto\EventHandler\Attributes\Handler;
use danog\MadelineProto\EventHandler\Attributes\Periodic;
use danog\MadelineProto\EventHandler\Filter\Combinator\FiltersAnd;
use danog\MadelineProto\EventHandler\Filter\Filter;
use danog\MadelineProto\EventHandler\Filter\FilterAllowAll;
use danog\MadelineProto\EventHandler\Update;
use Generator;
use mysqli;
use PDO;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Stmt\DeclareDeclare;
use PhpParser\NodeFinder;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PHPStan\PhpDocParser\Ast\NodeTraverser;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Revolt\EventLoop;
use Webmozart\Assert\Assert;

use function Amp\File\isDirectory;
use function Amp\File\isFile;
use function Amp\File\listFiles;
use function Amp\File\read;

/**
 * Event handler.
 */
abstract class EventHandler extends AbstractAPI
{
    use DbPropertiesTrait {
        DbPropertiesTrait::initDb as private internalInitDb;
    }
    private static bool $includingPlugins = false;
    /**
     * Start MadelineProto and the event handler.
     *
     * Also initializes error reporting, catching and reporting all errors surfacing from the event loop.
     *
     * @param string $session Session name
     * @param ?SettingsAbstract $settings Settings
     */
    final public static function startAndLoop(string $session, ?SettingsAbstract $settings = null): void
    {
        if (self::$includingPlugins) {
            throw new PluginRegistration(static::class);
        }
        $settings ??= new SettingsEmpty;
        $API = new API($session, $settings);
        $API->startAndLoopInternal(static::class);
    }
    /**
     * Start MadelineProto as a bot and the event handler.
     *
     * Also initializes error reporting, catching and reporting all errors surfacing from the event loop.
     *
     * @param string $session Session name
     * @param string $token Bot token
     * @param ?SettingsAbstract $settings Settings
     */
    final public static function startAndLoopBot(string $session, string $token, ?SettingsAbstract $settings = null): void
    {
        if (self::$includingPlugins) {
            throw new PluginRegistration(static::class);
        }
        $settings ??= new SettingsEmpty;
        $API = new API($session, $settings);
        $API->botLogin($token);
        $API->startAndLoopInternal(static::class);
    }
    /** @internal */
    final protected function reconnectFull(): bool
    {
        return true;
    }
    /**
     * Whether the event handler was started.
     */
    private bool $startedInternal = false;
    private ?LocalMutex $startMutex = null;
    private ?DeferredFuture $startDeferred = null;
    /**
     * @var array<PeriodicLoop>
     */
    private array $periodicLoops = [];
    /**
     * Start method handler.
     *
     * @internal
     */
    final public function internalStart(APIWrapper $MadelineProto, array $pluginsPrev, array &$pluginsNew, bool $main = true): ?array
    {
        if ($this->startedInternal) {
            return null;
        }
        $this->startMutex ??= new LocalMutex;
        $this->startDeferred ??= new DeferredFuture;
        $startDeferred = $this->startDeferred;
        $lock = $this->startMutex->acquire();
        try {
            $this->wrapper = $MadelineProto;
            $this->exportNamespaces();

            if (isset(static::$dbProperties)) {
                $this->internalInitDb($this->wrapper->getAPI());
            }
            if ($main) {
                $this->setReportPeers(Tools::call($this->getReportPeers())->await());
            }
            if (\method_exists($this, 'onStart')) {
                $r = $this->onStart();
                if ($r instanceof Generator) {
                    $r = Tools::consumeGenerator($r);
                }
                if ($r instanceof Future) {
                    $r = $r->await();
                }
            }
            if ($main) {
                $this->setReportPeers(Tools::call($this->getReportPeers())->await());
            }

            $constructors = $this->getTL()->getConstructors();
            $methods = [];
            $handlers = [];
            $has_any = false;
            $basic_handler = static function (array $update, Closure $closure): void {
                $r = $closure($update);
                if ($r instanceof Generator) {
                    Tools::consumeGenerator($r);
                }
            };
            foreach ((new ReflectionClass($this))->getMethods(ReflectionMethod::IS_PUBLIC) as $methodRefl) {
                $method = $methodRefl->getName();
                if ($method === 'onAny') {
                    $has_any = true;
                    continue;
                }
                $closure = $this->$method(...);
                $method_name = \lcfirst(\substr($method, 2));
                if (($constructor = $constructors->findByPredicate($method_name)) && $constructor['type'] === 'Update') {
                    $methods[$method_name] = [
                        function (array $update) use ($basic_handler, $closure): void {
                            EventLoop::queue($basic_handler, $update, $closure);
                        }
                    ];
                    continue;
                }
                if ($periodic = $methodRefl->getAttributes(Cron::class)) {
                    $periodic = $periodic[0]->newInstance();
                    $this->periodicLoops[$method] = new PeriodicLoop(
                        function (PeriodicLoop $loop) use ($closure): bool {
                            return $closure($loop) ?? false;
                        },
                        $method,
                        $periodic->period
                    );
                    $this->periodicLoops[$method]->start();
                    continue;
                }
                $filter = $methodRefl->getAttributes(
                    Filter::class,
                    ReflectionAttribute::IS_INSTANCEOF
                )[0] ?? null;
                if (!$filter) {
                    if (!($handler = $methodRefl->getAttributes(Handler::class))) {
                        continue;
                    }
                    $filter = new FilterAllowAll;
                } else {
                    $filter = $filter->newInstance();
                }
                $filter = new FiltersAnd(
                    $filter,
                    Filter::fromReflectionType($methodRefl->getParameters()[0]->getType())
                );
                $filter = $filter->initialize($this) ?? $filter;
                $handlers []= function (Update $update) use ($closure, $filter): void {
                    if ($filter->apply($update)) {
                        EventLoop::queue($closure, $update);
                    }
                };
            }
            if ($has_any) {
                $onAny = $this->onAny(...);
                foreach ($constructors->by_id as $constructor) {
                    if ($constructor['type'] === 'Update' && !isset($methods[$constructor['predicate']])) {
                        $methods[$constructor['predicate']] = [$onAny];
                    }
                }
            }

            $plugins = $this->internalGetPlugins();
            foreach ($plugins as $class) {
                $plugin = $pluginsPrev[$class] ?? $pluginsNew[$class] ?? new $class;
                $pluginsNew[$class] = $plugin;
                [$newMethods, $newHandlers] = $plugin->internalStart($MadelineProto, $pluginsPrev, $pluginsNew, false) ?? [];
                foreach ($newMethods as $update => $method) {
                    $methods[$update] ??= [];
                    $methods[$update][] = $method;
                }
                $handlers = \array_merge($handler, $newHandlers);
            }

            $this->startedInternal = true;
            return [$methods, $handlers];
        } finally {
            $this->startDeferred = null;
            $startDeferred->complete();
            $lock->release();
        }
    }
    /**
     * Obtain a PeriodicLoop instance created by the Periodic attribute.
     *
     * @param string $name Method name
     */
    final public function getPeriodicLoop(string $name): PeriodicLoop
    {
        return $this->periodicLoops[$name];
    }
    /**
     * @internal
     */
    final public function waitForInternalStart(): ?Future
    {
        if (!$this->startedInternal && !$this->startDeferred) {
            $this->startDeferred = new DeferredFuture;
        }
        return $this->startDeferred?->getFuture();
    }
    /**
     * Get peers where to send error reports.
     *
     * @return string|int|array<string|int>
     */
    public function getReportPeers()
    {
        return [];
    }
    /**
     * Obtain a path or a list of paths that will be recursively searched for plugins.
     *
     * Plugin filenames end with Plugin.php, and will be included automatically.
     *
     * @return non-empty-string|non-empty-list<non-empty-string>|null
     */
    public function getPluginPaths(): string|array|null
    {
        return null;
    }
    /**
     * Obtain a list of plugin event handlers to use, in addition with those found by getPluginPath.
     *
     * @return array<class-string<EventHandler>>
     */
    public function getPlugins(): array
    {
        return [];
    }

    private static array $includedPaths = [];
    /**
     * Obtain a list of plugin event handlers.
     */
    private function internalGetPlugins(): array
    {
        $paths = $this->getPluginPaths();
        if (\is_string($paths)) {
            $paths = [$paths];
        } elseif ($paths === null) {
            $paths = [];
        }

        $plugins = \array_values($this->getPlugins());

        $recurse = static function (string $path) use (&$recurse, &$plugins): void {
            foreach (listFiles($path) as $file) {
                if (isDirectory($file)) {
                    $recurse($file);
                } elseif (isFile($file) && \str_ends_with($file, "Plugin.php")) {
                    $file = \realpath($file);
                    if (isset(self::$includedPaths[$file])) {
                        continue;
                    }
                    self::$includedPaths[$file] = true;
                    try {
                        require $file;
                    } catch (PluginRegistration $e) {
                        $name = \substr($e->plugin, \strrpos($e->plugin, '\\')+1);
                        Assert::eq($name, \basename($file, '.php'));
                        $plugins []= $e->plugin;
                        continue;
                    }
                    throw new AssertionError("No plugin was registered after including $file!");
                }
            }
        };

        try {
            self::$includingPlugins = true;
            \array_map($recurse, $paths);
        } finally {
            self::$includingPlugins = false;
        }

        $plugins = \array_values(\array_unique($plugins, SORT_REGULAR));

        foreach ($plugins as $plugin) {
            Assert::classExists($plugin);
            Assert::true(\is_subclass_of($plugin, PluginEventHandler::class), "$plugin must extend ".PluginEventHandler::class);
            Assert::notEq($plugin, PluginEventHandler::class);
            Assert::true(\str_contains(\ltrim($plugin, '\\'), '\\'), "$plugin must be in a namespace!");
            self::validatePlugin($plugin);
        }

        return $plugins;
    }

    private const BANNED_FUNCTIONS = [
        'file_get_contents',
        'file_put_contents',
        'unlink',
        'curl_exec',
        'mysqli_query',
        'mysqli_connect',
        'mysql_connect',
        'fopen',
        'fsockopen',
    ];
    private const BANNED_FILE_FUNCTIONS = [
        'amp\\file\\read',
        'amp\\file\\write',
        'amp\\file\\get',
        'amp\\file\\put',
    ];
    private const BANNED_CLASSES = [
        PDO::class,
        mysqli::class,
    ];
    private static function validatePlugin(string $class): void
    {
        $file = read((new ReflectionClass($class))->getFileName());
        $file = (new ParserFactory)->create(ParserFactory::ONLY_PHP7)->parse($file);
        Assert::notNull($file);
        $traverser = new NodeTraverser([new NameResolver()]);
        $file = $traverser->traverse($file);
        $finder = new NodeFinder;

        /** @var DeclareDeclare|null $call */
        $declare = $finder->findFirstInstanceOf($file, DeclareDeclare::class);
        Assert::true(
            $declare !== null
            && $declare->key->name === 'strict_types'
            && $declare->value instanceof LNumber
            && $declare->value->value === 1,
            "An error occurred while analyzing plugin $class: for performance reasons, the first statement of a plugin must be declare(strict_types=1);"
        );

        /** @var FuncCall $call */
        foreach ($finder->findInstanceOf($file, FuncCall::class) as $call) {
            if (!$call->name instanceof Name) {
                continue;
            }

            $name = $call->name->toLowerString();
            if (\in_array($name, self::BANNED_FUNCTIONS, true)) {
                throw new AssertionError("An error occurred while analyzing plugin $class: for performance reasons, plugins may not use the non-async blocking function $name!");
            }
            if (\in_array($name, self::BANNED_FILE_FUNCTIONS, true)) {
                throw new AssertionError("An error occurred while analyzing plugin $class: for performance reasons, plugins may not use the file function $name, please use properties and __sleep to store plugin-related configuration in the session!");
            }
        }

        /** @var New_ $call */
        foreach ($finder->findInstanceOf($file, New_::class) as $new) {
            if ($new->class instanceof Name
                && \in_array($name = $new->class->toLowerString(), self::BANNED_CLASSES, true)
            ) {
                throw new AssertionError("An error occurred while analyzing plugin $class: for performance reasons, plugins may not use the non-async blocking class $name!");
            }
        }

        if ($finder->findFirstInstanceOf($file, Include_::class)) {
            throw new AssertionError("An error occurred while analyzing plugin $class: for performance reasons, plugins can only automatically include or require other files present in the plugins folder by triggering the PSR-4 autoloader (not by manually require()'ing them).");
        }
    }
}
