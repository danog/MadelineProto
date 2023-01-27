<?php

declare(strict_types=1);

/**
 * AnnotationsBuilder module.
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

use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\TL\TL;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

final class AnnotationsBuilder
{
    /**
     * Reflection classes.
     */
    private array $reflectionClasses = [];
    /**
     * Namespace.
     */
    private string $namespace;
    /**
     * TL instance.
     */
    private TL $TL;
    private array $blacklist;
    private array $blacklistHard;
    public function __construct(Logger $logger, array $settings, array $reflectionClasses, string $namespace)
    {
        $this->reflectionClasses = $reflectionClasses;
        $this->namespace = $namespace;
        /** @psalm-suppress InvalidArgument */
        $this->TL = new TL();
        $tlSchema = new TLSchema;
        $tlSchema->mergeArray($settings);
        $this->TL->init($tlSchema);
        $this->blacklist = \json_decode(\file_get_contents(__DIR__.'/../docs/template/disallow.json'), true);
        $this->blacklistHard = $this->blacklist;
        unset($this->blacklistHard['messages.getHistory'], $this->blacklistHard['channels.getMessages'], $this->blacklistHard['updates.getDifference'], $this->blacklistHard['updates.getChannelDifference'], $this->blacklistHard['updates.getState']);
        \file_put_contents(__DIR__.'/Namespace/Blacklist.php', '<?php
namespace danog\MadelineProto\Namespace;

final class Blacklist {
    public const BLACKLIST = '.\var_export($this->blacklistHard, true).';
}
        ');
    }
    public function mkAnnotations(): void
    {
        Logger::log('Generating annotations...', Logger::NOTICE);
        $this->createInternalClasses();
    }
    private function prepareTLType(string $type): string
    {
        return match ($type) {
            'string' => 'string',
            'bytes' => 'string',
            'int' => 'int',
            'long' => 'int',
            'double' => 'float',
            'float' => 'float',
            'Bool' => 'bool',
            'bool' => 'bool',
            default => 'array'
        };
    }
    private function prepareTLDefault(string $type): string
    {
        return match ($type) {
            'string' => "''",
            'bytes' => "''",
            'int' => '0',
            'long' => '0',
            'double' => '0.0',
            'float' => '0.0',
            'Bool' => 'false',
            'bool' => 'false',
            default => '[]'
        };
    }
    private function prepareTLTypeDescription(string $type): string
    {
        return match ($type) {
            'string' => '',
            'bytes' => '',
            'int' => '',
            'long' => '',
            'double' => '',
            'float' => '',
            'Bool' => '',
            'bool' => '',
            default => " @see https://docs.madelineproto.xyz/API_docs/types/$type.html"
        };
    }
    /**
     * Create internalDoc.
     */
    private function createInternalClasses(): void
    {
        Logger::log('Creating internal classes...', Logger::NOTICE);
        $internalDoc = [];
        foreach ($this->TL->getMethods()->by_id as $id => $data) {
            if (!\strpos($data['method'], '.')) {
                continue;
            }
            [$namespace, $method] = \explode('.', $data['method']);
            if (!\in_array($namespace, $this->TL->getMethodNamespaces())) {
                continue;
            }
            if (isset($this->blacklist[$data['method']])) {
                continue;
            }
            $internalDoc[$namespace][$method]['title'] = \str_replace(['](../', '.md'], ['](https://docs.madelineproto.xyz/API_docs/', '.html'], Lang::$lang['en']["method_{$data['method']}"] ?? '');
            $type = \str_ireplace(['vector<', '>'], [' of ', '[]'], $data['type']);
            foreach ($data['params'] as $param) {
                if (\in_array($param['name'], ['flags', 'flags2', 'random_id', 'random_bytes'])) {
                    continue;
                }
                if ($param['name'] === 'data' && $type === 'messages.SentEncryptedMessage') {
                    $param['name'] = 'message';
                    $param['type'] = 'DecryptedMessage';
                }
                if ($param['name'] === 'chat_id' && $data['method'] !== 'messages.discardEncryption') {
                    $param['type'] = 'InputPeer';
                }
                if ($param['name'] === 'hash' && $param['type'] === 'long') {
                    $param['pow'] = 'hi';
                    $param['type'] = 'Vector t';
                    $param['subtype'] = 'int';
                }
                if ($param['type'] === 'bool') {
                    $param['pow'] = 'hi';
                }
                $stype = 'type';
                if (isset($param['subtype'])) {
                    $stype = 'subtype';
                }
                $ptype = $param[$stype];
                switch ($ptype) {
                    case 'true':
                    case 'false':
                        $ptype = 'boolean';
                }
                $ptype = $stype === 'type' ? $ptype : "[{$ptype}]";
                $internalDoc[$namespace][$method]['attr'][$param['name']] = ['optional' => isset($param['pow']), 'type' => $ptype, 'description' => \str_replace(['](../', '.md'], ['](https://docs.madelineproto.xyz/API_docs/', '.html'], Lang::$lang['en']["method_{$data['method']}_param_{$param['name']}_type_{$param['type']}"] ?? '')];
            }
            if ($type === 'Bool') {
                $type = \strtolower($type);
            }
            $internalDoc[$namespace][$method]['return'] = $type;
        }
        $class = new ReflectionClass($this->reflectionClasses['MTProto']);
        $methods = $class->getMethods((ReflectionMethod::IS_STATIC & ReflectionMethod::IS_PUBLIC) | ReflectionMethod::IS_PUBLIC);
        $class = new ReflectionClass(Tools::class);
        $methods = \array_merge($methods, $class->getMethods((ReflectionMethod::IS_STATIC & ReflectionMethod::IS_PUBLIC) | ReflectionMethod::IS_PUBLIC));
        foreach ($methods as $key => $method) {
            $name = $method->getName();
            if ($name == 'methodCallAsyncRead') {
                unset($methods[\array_search('methodCall', $methods)]);
            } elseif (\strpos($name, '__') === 0) {
                unset($methods[$key]);
            } elseif (\stripos($name, 'async') !== false) {
                if (\strpos($name, '_async') !== false) {
                    unset($methods[\array_search(\str_ireplace('_async', '', $name), $methods)]);
                } else {
                    unset($methods[\array_search(\str_ireplace('async', '', $name), $methods)]);
                }
            }
        }

        $sortedMethods = [];
        foreach ($methods as $method) {
            $sortedMethods[$method->getName()] = $method;
        }
        \ksort($sortedMethods);
        $methods = \array_values($sortedMethods);

        foreach ($methods as $method) {
            $name = $method->getName();
            if (\strpos($method->getDocComment() ?: '', '@internal') !== false) {
                continue;
            }
            $static = $method->isStatic();
            if (!$static) {
                $code = \file_get_contents($method->getFileName());
                $code = \implode("\n", \array_slice(\explode("\n", $code), $method->getStartLine(), $method->getEndLine() - $method->getStartLine()));
                if (\strpos($code, '$this') === false) {
                    Logger::log("{$name} should be STATIC!", Logger::FATAL_ERROR);
                }
            }
            if ($name == 'methodCallAsyncRead') {
                $name = 'methodCall';
            } elseif (\stripos($name, 'async') !== false) {
                if (\strpos($name, '_async') !== false) {
                    $name = \str_ireplace('_async', '', $name);
                } else {
                    $name = \str_ireplace('async', '', $name);
                }
            }
            $name = StrTools::toCamelCase($name);
            $name = \str_ireplace(['mtproto', 'api'], ['MTProto', 'API'], $name);
            $doc = 'public ';
            if ($static) {
                $doc .= 'static ';
            }
            $doc .= 'function ';
            $doc .= $name;
            $doc .= '(';
            $paramList = '';
            $hasVariadic = false;
            foreach ($method->getParameters() as $param) {
                if ($type = $param->getType()) {
                    $doc .= $this->typeToStr($type).' ';
                } else {
                    Logger::log($name.'.'.$param->getName().' has no type!', Logger::WARNING);
                }
                if ($param->isVariadic()) {
                    $doc .= '...';
                }
                if ($param->isPassedByReference()) {
                    $doc .= '&';
                }
                $doc .= '$';
                $doc .= $param->getName();
                if ($param->isOptional() && !$param->isVariadic()) {
                    $doc .= ' = ';
                    if ($param->isDefaultValueConstant()) {
                        $doc .= '\\'.\str_replace(['NULL', 'self'], ['null', 'danog\\MadelineProto\\MTProto'], $param->getDefaultValueConstantName());
                    } else {
                        $doc .= \str_replace('NULL', 'null', \var_export($param->getDefaultValue(), true));
                    }
                }
                $doc .= ', ';
                if ($param->isVariadic()) {
                    $paramList .= '...';
                }
                $paramList .= '$'.$param->getName().', ';
            }
            $type = $method->getReturnType();
            $hasReturnValue = $type !== null;
            $doc = \rtrim($doc, ', ');
            $paramList = \rtrim($paramList, ', ');
            $doc .= ')';
            $async = true;
            if ($hasReturnValue && $static) {
                $doc .= ': ';
                $doc .= $this->typeToStr($type);
                $async = false;
            }
            if ($method->getDeclaringClass()->getName() == Tools::class) {
                $async = false;
            }
            if ($method->getDeclaringClass()->getName() == StrTools::class) {
                $async = false;
            }
            if ($method->getDeclaringClass()->getName() == AsyncTools::class) {
                $async = false;
            }
            $ret = $type && $type instanceof ReflectionNamedType && $type->getName() === 'void' ? '' : 'return';
            $doc .= "\n{\n";
            if ($async) {
                $doc .= "    {$ret} \$this->wrapper->getAPI()->{__FUNCTION__}({$paramList});\n";
            } elseif (!$static) {
                $doc .= "    {$ret} \$this->wrapper->getAPI()->{$name}({$paramList});\n";
            } else {
                $doc .= "    {$ret} \\".$method->getDeclaringClass()->getName().'::'.$name."({$paramList});\n";
            }
            $doc .= "}\n";
            if (!$method->getDocComment()) {
                Logger::log("{$name} has no PHPDOC!", Logger::FATAL_ERROR);
            }
            if (!$type) {
                Logger::log("{$name} has no return type!", Logger::FATAL_ERROR);
            }
            $promise = '\\';
            $phpdoc = $method->getDocComment() ?: '';
            if (!\str_contains($phpdoc, '@return')) {
                if (!\trim($phpdoc)) {
                    $phpdoc = '/** @return '.$type.' */';
                } else {
                    $phpdoc = \str_replace('*/', ' * @return '.$type."\n     */", $phpdoc);
                }
            }
            $phpdoc = \str_replace('@return \\Generator', "@return $promise", $phpdoc);
            $phpdoc = \str_replace('@return \\Promise', "@return $promise", $phpdoc);
            $phpdoc = \str_replace('@return Generator', "@return $promise", $phpdoc);
            $phpdoc = \str_replace('@return Promise', "@return $promise", $phpdoc);
            if ($hasReturnValue && $async && \preg_match('/@return (.*)/', $phpdoc, $matches)) {
                $ret = $matches[1];
                $new = $ret;
                if ($type && !\str_contains($ret, '<')) {
                    $new = $this->typeToStr($type);
                }
                $phpdoc = \str_replace('@return '.$ret, '@return mixed', $phpdoc);
                if (!\str_contains($phpdoc, '@return')) {
                    $phpdoc = \str_replace('@return ', "@return $new|$promise<$new>\n     * @return ", $phpdoc);
                }
            }
            $phpdoc = \preg_replace(
                '/@return \\\\Generator<(?:[^,]+), (?:[^,]+), (?:[^,]+), (.+)>/',
                "@return $promise<$1>",
                $phpdoc,
            );
            $internalDoc['InternalDoc'][$name]['method'] = $phpdoc;
            $internalDoc['InternalDoc'][$name]['method'] .= "\n    ".\implode("\n    ", \explode("\n", $doc));
        }
        foreach ($internalDoc as $namespace => $methods) {
            if ($namespace === 'InternalDoc') {
                $handle = \fopen(__DIR__.'/InternalDoc.php', 'w');
                \fwrite($handle, "<?php\n");
                \fwrite($handle, "/**\n");
                \fwrite($handle, " * This file is automatically generated by the build_docs.php file\n");
                \fwrite($handle, " * and is used only for autocompletion in multiple IDEs\n");
                \fwrite($handle, " * don't modify it manually.\n");
                \fwrite($handle, " */\n\n");
                \fwrite($handle, "namespace {$this->namespace};\n");

                \fwrite($handle, "\nabstract class {$namespace}\n{\nprotected APIWrapper \$wrapper;\n");
                foreach ($this->TL->getMethodNamespaces() as $namespace) {
                    $namespaceInterface = '\\danog\\MadelineProto\\Namespace\\'.\ucfirst($namespace);
                    \fwrite($handle, '/** @var \\danog\\MadelineProto\\Namespace\\AbstractAPI&'.$namespaceInterface.' $'.$namespace." */\n");
                    \fwrite($handle, 'public readonly \\danog\\MadelineProto\\Namespace\\AbstractAPI $'.$namespace.";\n");
                }
                \fwrite($handle, '
                    /**
                     * Export APIFactory instance with the specified namespace.
                     * @psalm-suppress InaccessibleProperty
                     */
                    protected function exportNamespaces(): void
                    {
                ');
                foreach ($this->TL->getMethodNamespaces() as $namespace) {
                    \fwrite($handle, "\$this->$namespace ??= new \\danog\\MadelineProto\\Namespace\\AbstractAPI('$namespace');\n");
                    \fwrite($handle, "\$this->{$namespace}->setWrapper(\$this->wrapper);\n");
                }
                \fwrite($handle, "}\n");
            } else {
                $namespace = \ucfirst($namespace);
                $handle = \fopen(__DIR__."/Namespace/$namespace.php", 'w');
                \fwrite($handle, "<?php\n");
                \fwrite($handle, "/**\n");
                \fwrite($handle, " * This file is automatic generated by build_docs.php file\n");
                \fwrite($handle, " * and is used only for autocomplete in multiple IDE\n");
                \fwrite($handle, " * don't modify manually.\n");
                \fwrite($handle, " */\n\n");
                \fwrite($handle, "namespace {$this->namespace}\\Namespace;\n");

                \fwrite($handle, "\ninterface {$namespace}\n{");
            }
            foreach ($methods as $method => $properties) {
                if (isset($properties['method'])) {
                    \fwrite($handle, $properties['method']);
                    continue;
                }
                $title = \implode("\n     * ", \explode("\n", $properties['title']));
                \fwrite($handle, "\n    /**\n");
                \fwrite($handle, "     * {$title}\n");
                \fwrite($handle, "     *\n");
                $params = [];
                if (isset($properties['attr'])) {
                    \uasort($properties['attr'], fn (array $arr1, array $arr2) => $arr1['optional'] <=> $arr2['optional']);
                    foreach ($properties['attr'] as $name => $param) {
                        $param['type'] = $this->prepareTLType($param['type']);
                        $param_var = $param['type'].' $'.$name;
                        if ($param['optional']) {
                            $param_var .= ' = '.$this->prepareTLDefault($param['type']);
                        }
                        $params []= $param_var;
                        $param['description'] .= $this->prepareTLTypeDescription($param['type']);
                        \fwrite($handle, "     * @param {$param['type']} \${$name} {$param['description']}\n");
                    }
                    \fwrite($handle, "     * \n");
                    \fwrite($handle, "     *\n");
                }
                $properties['return'] = $this->prepareTLType($properties['return']);
                $properties['return'] .= $this->prepareTLTypeDescription($properties['return']);
                \fwrite($handle, "     * @return array\n");
                \fwrite($handle, "     */\n");
                \fwrite($handle, "    public function {$method}(");
                if (isset($properties['attr'])) {
                    \fwrite($handle, \implode(', ', $params));
                }
                \fwrite($handle, ");\n");
            }
            \fwrite($handle, "}\n");
        }
        \fclose($handle);
    }

    private function typeToStr(ReflectionType $type): string
    {
        $new = '';
        if ($type instanceof ReflectionNamedType) {
            if ($type->allowsNull() && $type->getName() !== 'mixed') {
                $new .= '?';
            }
            if (!$type->isBuiltin()) {
                $new .= '\\';
            }
            $new .= $type->getName() === 'self' ? $this->reflectionClasses['API'] : $type->getName();
        } elseif ($type instanceof ReflectionUnionType) {
            return \implode('|', \array_map($this->typeToStr(...), $type->getTypes()));
        }
        return $new;
    }
}
