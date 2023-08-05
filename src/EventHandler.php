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
use danog\MadelineProto\EventHandler\Filter\Combinator\FiltersAnd;
use danog\MadelineProto\EventHandler\Filter\Filter;
use danog\MadelineProto\EventHandler\Filter\FilterAllowAll;
use danog\MadelineProto\EventHandler\Update;
use Generator;
use PhpParser\Node\Name;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Revolt\EventLoop;
use Webmozart\Assert\Assert;

use function Amp\File\isDirectory;
use function Amp\File\isFile;
use function Amp\File\listFiles;

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
            return;
        }
        self::cachePlugins(static::class);
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
            return;
        }
        self::cachePlugins(static::class);
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
            if ($this instanceof PluginEventHandler && !$this->isPluginEnabled()) {
                return [[], []];
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
                if (!$this instanceof SimpleEventHandler) {
                    continue;
                }

                \array_map(fn (ReflectionAttribute $attribute) => $attribute->newInstance(), $methodRefl->getAttributes());

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
                $filter = $filter->initialize($this);
                $handlers []= function (Update $update) use ($closure, $filter): void {
                    if ($filter->apply($update)) {
                        EventLoop::queue($closure, $update);
                    }
                };
            }
            if ($this instanceof SimpleEventHandler) {
                $last = null;
                foreach (Tools::validateEventHandlerClass(static::class) as $issue) {
                    if ($issue->severe) {
                        $last = $issue;
                    }
                    $issue->log();
                }
                if ($last) {
                    $last->throw();
                }
            }
            if ($has_any) {
                /** @psalm-suppress UndefinedMethod */
                $onAny = $this->onAny(...);
                foreach ($constructors->by_id as $constructor) {
                    if ($constructor['type'] === 'Update' && !isset($methods[$constructor['predicate']])) {
                        $methods[$constructor['predicate']] = [$onAny];
                    }
                }
            }

            $plugins = self::$pluginCache[static::class];
            foreach ($plugins as $class) {
                $plugin = $pluginsPrev[$class] ?? $pluginsNew[$class] ?? new $class;
                $pluginsNew[$class] = $plugin;
                [$newMethods, $newHandlers] = $plugin->internalStart($MadelineProto, $pluginsPrev, $pluginsNew, false) ?? [];
                if (!$plugin->isPluginEnabled()) {
                    unset($pluginsNew[$class]);
                    continue;
                }
                foreach ($newMethods as $update => $method) {
                    $methods[$update] ??= [];
                    $methods[$update][] = $method;
                }
                $handlers = \array_merge($handlers, $newHandlers);
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
     * Obtain a PeriodicLoop instance created by the Cron attribute.
     *
     * @param string $name Method name
     */
    final public function getPeriodicLoop(string $name): PeriodicLoop
    {
        return $this->periodicLoops[$name];
    }
    /**
     * Obtain all PeriodicLoop instances created by the Cron attribute.
     *
     * @return array<string, PeriodicLoop>
     */
    final public function getPeriodicLoops(): array
    {
        return $this->periodicLoops;
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
    public static function getPluginPaths(): string|array|null
    {
        return null;
    }
    /**
     * Obtain a list of plugin event handlers to use, in addition with those found by getPluginPath.
     *
     * @return array<class-string<EventHandler>>
     */
    public static function getPlugins(): array
    {
        return [];
    }

    /** @var array<class-string<EventHandler>, list<class-string<PluginEventHandler>>> */
    private static array $pluginCache = [];

    /**
     * Cache a list of plugin event handlers.
     *
     * @internal
     *
     * @param class-string<EventHandler> $class
     */
    final public static function cachePlugins(string $class): void
    {
        if (isset(self::$pluginCache[$class])) {
            return;
        }
        $plugins = $class::getPlugins();
        $plugins = \array_values(\array_unique($plugins, SORT_REGULAR));
        $plugins = \array_merge($plugins, self::internalGetDirectoryPlugins($class));

        foreach ($plugins as $plugin) {
            Assert::classExists($plugin);
            Assert::true(\is_subclass_of($plugin, PluginEventHandler::class), "$plugin must extend ".PluginEventHandler::class);
            Assert::notEq($plugin, PluginEventHandler::class);
            Assert::true(\str_contains(\ltrim($plugin, '\\'), '\\'), "$plugin must be in a namespace!");
            $last = null;
            foreach (Tools::validateEventHandlerClass($plugin) as $issue) {
                if ($issue->severe) {
                    $last = $issue;
                }
                $issue->log();
            }
            if ($last) {
                $last->throw();
            }
        }

        self::$pluginCache[$class] = $plugins;
    }

    private static array $checkedPaths = [];
    /**
     * Cache a list of plugin event handlers.
     *
     * @param class-string<EventHandler> $class
     */
    private static function internalGetDirectoryPlugins(string $class): array
    {
        if (\is_subclass_of($class, PluginEventHandler::class)) {
            return [];
        }

        $paths = $class::getPluginPaths();
        if (\is_string($paths)) {
            $paths = [$paths];
        } elseif ($paths === null) {
            $paths = [];
        } else {
            $paths = \array_values($paths);
        }
        foreach ($paths as $k => &$path) {
            $pathNew = \realpath($path);
            if ($pathNew === false) {
                $pathNew = \realpath(\dirname((new ReflectionClass($class))->getFileName()).DIRECTORY_SEPARATOR.$path);
                if ($pathNew === false) {
                    unset($paths[$k]);
                    Logger::log(\sprintf(Lang::$current_lang['plugin_path_does_not_exist'], $path), Logger::FATAL_ERROR);
                    continue;
                }
            }
            $path = $pathNew;
        }

        if (!$paths) {
            return [];
        }

        $pluginsTemp = [];
        $recurse = static function (string $path, string $namespace = 'MadelinePlugin') use (&$recurse, &$pluginsTemp): void {
            foreach (listFiles($path) as $file) {
                $file = $path.DIRECTORY_SEPARATOR.$file;
                if (isDirectory($file)) {
                    $recurse($file, $namespace.'\\'.\basename($file));
                } elseif (isFile($file) && \str_ends_with($file, ".php")) {
                    $file = \realpath($file);
                    $fileName = \basename($file, '.php');
                    if ($fileName === 'functions') {
                        require $file;
                        continue;
                    }
                    if (\str_contains($fileName, '.')) {
                        continue;
                    }
                    $class = $namespace.'\\'.$fileName;
                    $refl = new ReflectionClass($class);
                    if ($refl->getFileName() !== $file) {
                        throw new AssertionError("$class was not defined when including $file, the same plugin is present in multiple plugin paths/composer!");
                    }
                    if (\class_exists($class)
                        && !$refl->isAbstract()
                        && \is_subclass_of($class, PluginEventHandler::class)
                    ) {
                        self::cachePlugins($class);
                        $pluginsTemp []= $class;
                    }
                }
            }
        };

        $plugins = [];
        try {
            self::$includingPlugins = true;
            foreach ($paths as $p) {
                if (isset(self::$checkedPaths[$p])) {
                    $plugins = \array_merge($plugins, self::$checkedPaths[$p]);
                    continue;
                }

                \spl_autoload_register(function (string $class) use ($p): void {
                    if (!\str_starts_with($class, 'MadelinePlugin\\')) {
                        return;
                    }
                    // Has leading /
                    $file = $p.\str_replace('\\', DIRECTORY_SEPARATOR, \substr($class, 14)).'.php';
                    if (\file_exists($file)) {
                        require $file;
                        if (!\class_exists($class) && !\interface_exists($class) && !\trait_exists($class) && !\enum_exists($class)) {
                            throw new AssertionError("$class was not defined when including $file!");
                        }
                    }
                });

                $recurse($p);
                self::$checkedPaths[$p] = $pluginsTemp;
                $plugins = \array_merge($plugins, $pluginsTemp);
                $pluginsTemp = [];
            }
        } finally {
            self::$includingPlugins = false;
        }

        return $plugins;
    }
}
