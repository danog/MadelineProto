<?php
/**
 * PhpDocBuilder module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use danog\ClassFinder\ClassFinder;
use danog\MadelineProto\PhpDoc\ClassDoc;
use danog\MadelineProto\PhpDoc\FunctionDoc;
use phpDocumentor\Reflection\DocBlock\Tags\Author;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use ReflectionFunction;

class PhpDocBuilder
{
    /**
     * Namespace.
     */
    private string $namespace;
    /**
     * Scan mode.
     */
    private int $mode;
    /**
     * Docblock factory.
     */
    private DocBlockFactory $factory;
    /**
     * Authors.
     */
    private array $authors = [];
    /**
     * Classes/interfaces/traits to ignore.
     *
     * @var ?callable
     * @psalm-var null|callable(class-string)
     */
    private $ignore;
    /**
     * Output directory.
     */
    private string $output;
    /**
     * Use map.
     *
     * array<class-string, array<class-string, class-string>>
     */
    private array $useMap = [];
    /**
     * Create docblock builder.
     *
     * @param string $namespace Namespace (defaults to package namespace)
     * @param int    $mode      Finder mode, an OR-selection of ClassFinder::ALLOW_*
     *
     * @return self
     */
    public static function fromNamespace(string $namespace = '', int $mode = ClassFinder::ALLOW_ALL): self
    {
        return new self($namespace, $mode);
    }
    /**
     * Constructor.
     *
     * @param string $namespace
     * @param int    $mode
     */
    private function __construct(string $namespace, int $mode)
    {
        $this->factory = DocBlockFactory::createInstance();
        $this->namespace = $namespace;
        $this->mode = $mode;

        $appRoot = new \danog\ClassFinder\AppConfig;
        $appRoot = $appRoot->getAppRoot();
        $appRoot .= "/composer.json";
        $json = \json_decode(\file_get_contents($appRoot), true);
        $authors = $json['authors'] ?? [];

        foreach ($authors as $author) {
            $this->authors []= new Author($author['name'], $author['email']);
        }

        if (!$this->namespace) {
            $namespaces = array_keys($json['autoload']['psr-4']);
            $this->namespace = $namespaces[0];
            foreach ($namespaces as $namespace) {
                if (strlen($namespace) && strlen($namespace) < strlen($this->namespace)) {
                    $this->namespace = $namespace;
                }
            }
        }
    }
    /**
     * Set filter to ignore certain classes.
     *
     * @param callable $ignore
     *
     * @psalm-param callable(class-string) $ignore
     *
     * @return self
     */
    public function setFilter(callable $ignore): self
    {
        $this->ignore = $ignore;

        return $this;
    }
    /**
     * Set output directory.
     *
     * @param string $output Output directory
     *
     * @return self
     */
    public function setOutput(string $output): self
    {
        $this->output = $output;

        return $this;
    }
    /**
     * Run documentor.
     *
     * @return self
     */
    public function run(): self
    {
        $classes = ClassFinder::getClassesInNamespace($this->namespace, $this->mode | ClassFinder::RECURSIVE_MODE);
        foreach ($classes as $class) {
            $this->addTypeAliases($class);
        }
        foreach ($classes as $class) {
            if ($this->ignore && $this->shouldIgnore($class)) {
                continue;
            }
            $class = \function_exists($class)
                ? new ReflectionFunction($class)
                : new ReflectionClass($class);
            $this->generate($class);
        }

        return $this;
    }
    /**
     * Resolve type alias.
     *
     * @internal
     *
     * @param string $fromClass Class from where this function is called
     * @param string $name      Name to resolve
     *
     * @psalm-param class-string $fromClass Class from where this function is called
     * @psalm-param class-string $name      Name to resolve
     *
     * @return string
     */
    public function resolveTypeAlias(string $fromClass, string $name): string
    {
        return $this->useMap[$fromClass][$name] ?? $name;
    }
    /**
     * Add type alias.
     *
     * @param string $class
     *
     * @psalm-param class-string $class
     *
     * @return void
     */
    private function addTypeAliases(string $class)
    {
        $reflectionClass = \function_exists($class)
            ? new ReflectionFunction($class)
            : new ReflectionClass($class);
        $payload = \file_get_contents($reflectionClass->getFileName());
        \preg_match_all("/use *(function)? +(.*?)(?: +as +(.+))? *;/", $payload, $matches, PREG_SET_ORDER|PREG_UNMATCHED_AS_NULL);
        foreach ($matches as [, $function, $import, $alias]) {
            $import = "\\$import";
            $alias ??= \basename(\str_replace('\\', '/', $import));
            $this->useMap[$class][$alias] = $import;
            $this->useMap[$class]['\\'.$alias] = $import;
        }
    }
    /**
     * Create directory recursively.
     *
     * @param string $file
     * @return string
     */
    private static function createDir(string $file): string
    {
        $dir = \dirname($file);
        if (!\file_exists($dir)) {
            self::createDir($dir);
            \mkdir($dir);
        }
        return $file;
    }

    /**
     * Generate documentation for class.
     *
     * @param ReflectionClass|ReflectionFunction $class Class
     *
     * @return void
     */
    private function generate($class): void
    {
        $name = $class->getName();
        $fName = $this->output;
        $fName .= \str_replace('\\', DIRECTORY_SEPARATOR, $name);
        $fName .= '.md';

        $class = $class instanceof ReflectionFunction
            ? new FunctionDoc($this, $class)
            : new ClassDoc($this, $class);
        if ($class->shouldIgnore()) {
            return;
        }
        $class = $class->format();

        $handle = \fopen(self::createDir($fName), 'w+');
        \fwrite($handle, $class);
        \fclose($handle);
    }

    /**
     * Get docblock factory.
     *
     * @internal
     *
     * @return DocBlockFactory
     */
    public function getFactory(): DocBlockFactory
    {
        return $this->factory;
    }

    /**
     * Whether should ignore trait/class/interface.
     *
     * @return bool
     */
    public function shouldIgnore(string $class): bool
    {
        return !($this->ignore)($class);
    }

    /**
     * Get authors.
     *
     * @return Author[]
     */
    public function getAuthors(): array
    {
        return $this->authors;
    }

    /**
     * Set authors.
     *
     * @param Author[] $authors Authors
     *
     * @return self
     */
    public function setAuthors(array $authors): self
    {
        $this->authors = $authors;

        return $this;
    }
}
