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

use AssertionError;
use danog\ClassFinder\ClassFinder;
use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\TL\TL;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

/**
 * @internal
 */
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
    private array $special;
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
        unset($this->blacklistHard['messages.getHistory'], $this->blacklistHard['channels.getMessages'], $this->blacklistHard['messages.getMessages']);
        \file_put_contents(__DIR__.'/Namespace/Blacklist.php', '<?php
namespace danog\MadelineProto\Namespace;

final class Blacklist {
    public const BLACKLIST = '.\var_export($this->blacklistHard, true).';
}
        ');
        $special = [];
        foreach (DocsBuilder::DEFAULT_TEMPLATES as $key => $types) {
            $replace = match ($key) {
                'User' => 'array|int|string',
                'InputFile' => 'mixed',
                'PhoneCall' => '\\danog\\MadelineProto\\VoIP|array',
                default => 'array'
            };
            foreach ($types as $type) {
                $special[$type] = $replace;
            }
        }
        $this->special = $special;
    }
    public function mkAnnotations(): void
    {
        Logger::log('Generating annotations...', Logger::NOTICE);
        $this->createInternalClasses();
    }
    private static function isVector(string $type): array
    {
        if (\str_contains($type, '<')) {
            return [true, \str_replace(['Vector<', '>'], '', $type)];
        }
        return [false, $type];
    }
    private function prepareTLType(string $type): string
    {
        [$isVector, $type] = self::isVector($type);
        if ($isVector) {
            return 'array';
        }
        return match ($type) {
            'string' => 'string',
            'bytes' => 'string',
            'waveform' => 'array',
            'int' => 'int',
            'long' => 'int',
            'strlong' => 'int',
            'double' => 'float',
            'float' => 'float',
            'Bool' => 'bool',
            'true' => 'bool',
            'InputMessage' => 'array|int',
            'InputMedia' => '\\danog\\MadelineProto\\EventHandler\\Media|array|string',
            'InputCheckPasswordSRP' => 'string|array',
            'DataJSON' => 'mixed',
            'JSONValue' => 'mixed',
            default => $this->special[$type] ?? 'array'
        };
    }
    private function prepareTLPsalmType(string $type, bool $input, int $depth = -2, array $stack = []): string
    {
        [$isVector, $type] = self::isVector($type);
        $base = match ($type) {
            'string' => 'string',
            'bytes' => 'string',
            'waveform' => 'non-empty-list<int<0, 31>>',
            'int' => 'int',
            'long' => 'int',
            'strlong' => 'int',
            'double' => 'float',
            'float' => 'float',
            'Bool' => 'bool',
            'true' => 'bool',
            'Updates' => 'array',
            'InputCheckPasswordSRP' => 'string|array',
            'DataJSON' => 'mixed',
            'JSONValue' => 'mixed',
            default => $this->special[$type] ?? ''
        };
        if ($type === 'channels.AdminLogResults') {
            $depth = 3;
        }
        if ($type === 'messages.Messages') {
            $depth = 3;
        }
        if ($type === 'messages.Dialogs') {
            $depth = 3;
        }
        if ($type === 'MessageMedia') {
            $depth = 2;
        }
        if ($type === 'DecryptedMessage' || $type === 'messages.BotResults' || $type === 'InputBotInlineResult') {
            $depth = 2;
        }
        if ($type === 'WebPage') {
            $depth = 3;
        }
        if ($type === 'Document' || $type === 'Photo') {
            $depth = 3;
        }
        if ($depth > 3) {
            $base = 'array';
        }
        if (!$base) {
            $constructors = [];
            foreach ($this->TL->getConstructors()->by_id as $constructor) {
                if ($constructor['type'] === $type) {
                    $params = ["_: '{$constructor['predicate']}'"];
                    foreach ($this->filterParams($constructor['params'], $constructor['type']) as $name => $param) {
                        if (isset($stack[$param['type']])) {
                            continue 2;
                        }
                        $stack[$param['type']] = true;
                        if ($input) {
                            $optional = isset($param['pow']) ? '?' : '';
                        } else {
                            $optional = isset($param['pow']) && \is_int($param['pow']) && $param['type'] !== 'true'
                                ? '?'
                                : '';
                        }
                        $params []= "$name$optional: ".$this->prepareTLPsalmType($param['type'], $input, $depth+1, $stack);
                        unset($stack[$param['type']]);
                    }
                    $params = \implode(', ', $params);
                    $constructors []= 'array{'.$params.'}';
                }
            }
            if ($constructors === []) {
                throw new AssertionError("No constructors for $type!");
            }
            $base = \implode('|', $constructors);
        }
        if ($type === 'InputMessage') {
            $base = "int|$base";
        }
        if ($type === 'InputMedia') {
            $base = "\\danog\\MadelineProto\\EventHandler\\Media|string|$base";
        }
        if ($isVector) {
            $base = "list<$base>";
        }
        return $base;
    }
    private function prepareTLDefault(string $type): string
    {
        [$isVector, $type] = self::isVector($type);
        if ($isVector) {
            return '[]';
        }
        return match ($type) {
            'string' => "''",
            'bytes' => "''",
            'int' => '0',
            'long' => '0',
            'strlong' => '0',
            'double' => '0.0',
            'float' => '0.0',
            'Bool' => 'false',
            'true' => 'false',
            'DataJSON' => 'null',
            'JSONValue' => 'null',
            default => '[]'
        };
    }
    private function preparePsalmDefault(string $type): string
    {
        [$isVector, $type] = self::isVector($type);
        if ($isVector) {
            return 'array<never, never>';
        }
        return match ($type) {
            'string' => "''",
            'bytes' => "''",
            'int' => '0',
            'long' => '0',
            'strlong' => '0',
            'double' => '0.0',
            'float' => '0.0',
            'Bool' => 'false',
            'true' => 'false',
            'DataJSON' => 'null',
            'JSONValue' => 'null',
            default => 'array<never, never>'
        };
    }
    private function prepareTLTypeDescription(string $type, string $description): string
    {
        [$isList, $type] = self::isVector($type);
        if ($isList) {
            $descriptionEnhanced = "Array of $description";
        } else {
            $descriptionEnhanced = $description;
        }
        return match ($type) {
            'string' => $description,
            'bytes' => $description,
            'waveform' => $description,
            'int' => $description,
            'long' => $description,
            'strlong' => $description,
            'double' => $description,
            'float' => $description,
            'Bool' => $description,
            'true' => $description,
            'DataJSON' => 'Any JSON-encodable data',
            'JSONValue' => 'Any JSON-encodable data',
            'InputFile' => 'A file name or a file URL. You can also use amphp async streams, amphp HTTP response objects, and [much more](https://docs.madelineproto.xyz/docs/FILES.html#downloading-files)!',
            default => $descriptionEnhanced
                ? "$descriptionEnhanced @see https://docs.madelineproto.xyz/API_docs/types/$type.html"
                : "@see https://docs.madelineproto.xyz/API_docs/types/$type.html"
        };
    }
    private function filterParams(array $params, string $type, ?string $method = null): array
    {
        $newParams = [];
        foreach ($params as $param) {
            if (\in_array($param['name'], ['flags', 'flags2', 'random_id', 'random_bytes'], true)) {
                continue;
            }
            if ($method) {
                $param['description'] = \str_replace(['](../', '.md'], ['](https://docs.madelineproto.xyz/API_docs/', '.html'], Lang::$lang['en']["method_{$method}_param_{$param['name']}_type_{$param['type']}"] ?? '');
            }
            $param['type'] = \ltrim($param['type'], '%');
            if ($param['name'] === 'data' && $type === 'messages.SentEncryptedMessage') {
                $param['name'] = 'message';
                $param['type'] = 'DecryptedMessage';
            }
            if ($param['name'] === 'bytes' && $type === 'EncryptedMessage') {
                $param['name'] = 'decrypted_message';
                $param['type'] = 'DecryptedMessage';
            }
            if ($type === 'DecryptedMessageMedia' && \in_array($param['name'], ['key', 'iv'], true)) {
                continue;
            }
            if ($param['name'] === 'chat_id' && $method !== 'messages.discardEncryption') {
                $param['type'] = 'InputPeer';
            }
            if ($param['name'] === 'hash' && $param['type'] === 'long') {
                $param['pow'] = 'optional';
                $param['type'] = 'Vector t';
                $param['subtype'] = 'int';
            }
            if (\in_array($param['type'], ['int', 'long', 'strlong', 'string', 'bytes'], true)) {
                $param['pow'] = 'optional';
            }
            $param['array'] = isset($param['subtype']);
            if ($param['array']) {
                $param['subtype'] = \ltrim($param['subtype'], '%');
                $param['type'] = 'Vector<'.$param['subtype'].'>';
                $param['pow'] = 'optional';
            }
            $newParams[$param['name']] = $param;
        }
        \uasort($newParams, fn (array $arr1, array $arr2) => isset($arr1['pow']) <=> isset($arr2['pow']));
        return $newParams;
    }
    private function prepareTLParams(array $data): array
    {
        [
            'params' => $params,
            'type' => $type,
            'method' => $method
        ] = $data;
        $params = $this->filterParams($params, $type, $method);
        $contents = '';
        $signature = [];
        foreach ($params as $name => $param) {
            $description = $this->prepareTLTypeDescription($param['type'], $param['description']);
            $psalmType = $this->prepareTLPsalmType($param['type'], true);
            $type = $this->prepareTLType($param['type']);
            $param_var = $type.' $'.$name;
            if (isset($param['pow'])) {
                $param_var .= ' = '.$this->prepareTLDefault($param['type']);
                $psalmDef = $this->preparePsalmDefault($param['type']);
                if ($psalmDef === 'array<never, never>') {
                    $psalmType .= '|'.$psalmDef;
                }
            }
            $signature []= $param_var;
            $contents .= "     * @param {$psalmType} \${$name} {$description}\n";

            if ($name === 'entities') {
                $contents .= "     * @param \\danog\\MadelineProto\\ParseMode \$parse_mode Whether to parse HTML or Markdown markup in the message\n";
                $signature []= "\\danog\\MadelineProto\\ParseMode \$parse_mode = \\danog\\MadelineProto\\ParseMode::TEXT";
            }
            if ($name === 'reply_to') {
                $contents .= "     * @param int \$reply_to_msg_id ID Of message to reply to\n";
                $signature []= "int \$reply_to_msg_id = 0";
                $contents .= "     * @param int \$top_msg_id This field must contain the topic ID only when replying to messages in forum topics different from the \"General\" topic (i.e. reply_to_msg_id is set and reply_to_msg_id != topicID and topicID != 1). \n";
                $signature []= "int \$top_msg_id = 0";
            }
        }
        return [$contents, $signature];
    }
    /**
     * Create internalDoc.
     */
    private function createInternalClasses(): void
    {
        Logger::log('Creating internal classes...', Logger::NOTICE);
        $internalDoc = [];
        foreach ($this->TL->getMethods()->by_id as $data) {
            if (!\strpos($data['method'], '.')) {
                continue;
            }
            [$namespace, $method] = \explode('.', $data['method']);
            if (!\in_array($namespace, $this->TL->getMethodNamespaces(), true)) {
                continue;
            }
            if (isset($this->blacklist[$data['method']])) {
                continue;
            }

            $title = \str_replace(['](../', '.md'], ['](https://docs.madelineproto.xyz/API_docs/', '.html'], Lang::$lang['en']["method_{$data['method']}"] ?? '');
            $title = \implode("\n     * ", \explode("\n", $title));
            $contents = "\n    /**\n";
            $contents .= "     * {$title}\n";
            $contents .= "     *\n";
            [$params, $signature] = $this->prepareTLParams($data);
            $contents .= $params;
            $returnType = $this->prepareTLType($data['type']);
            $psalmType = $this->prepareTLPsalmType($data['type'], false);
            $description = $this->prepareTLTypeDescription($data['type'], '');
            $contents .= "     * @return {$psalmType} {$description}\n";
            $contents .= "     */\n";
            $contents .= "    public function {$method}(";
            $contents .= \implode(', ', $signature);
            $contents .= "): {$returnType};\n";

            $internalDoc[$namespace][$method] = $contents;
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
            if ($hasReturnValue) {
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
                $doc .= "    {$ret} \$this->wrapper->getAPI()->{$name}({$paramList});\n";
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
            $phpdoc = $method->getDocComment() ?: '';
            $internalDoc['InternalDoc'][$name] = $phpdoc;
            $internalDoc['InternalDoc'][$name] .= "\n    ".\implode("\n    ", \explode("\n", $doc));
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
                \fwrite($handle, "use Generator;\n");
                \fwrite($handle, "use Amp\\Future;\n");
                \fwrite($handle, "use Closure;\n");
                \fwrite($handle, "use __PHP_Incomplete_Class;\n");
                \fwrite($handle, "use Amp\\ByteStream\\WritableStream;\n");
                \fwrite($handle, "use Amp\\ByteStream\\ReadableStream;\n");
                \fwrite($handle, "use Amp\\Cancellation;\n");
                \fwrite($handle, "use Amp\\Http\\Server\\Request as ServerRequest;\n");
                \fwrite($handle, "use danog\\MadelineProto\\Broadcast\\Action;\n");
                foreach (ClassFinder::getClassesInNamespace(\danog\MadelineProto\EventHandler::class, ClassFinder::RECURSIVE_MODE) as $class) {
                    \fwrite($handle, "use $class;\n");
                }
                /** @psalm-suppress UndefinedClass */
                foreach (ClassFinder::getClassesInNamespace(\danog\MadelineProto\Ipc::class, ClassFinder::RECURSIVE_MODE) as $class) {
                    if (\str_contains($class, 'Wrapper')) {
                        continue;
                    }
                    \fwrite($handle, "use $class;\n");
                }
                /** @psalm-suppress UndefinedClass */
                foreach (ClassFinder::getClassesInNamespace(\danog\MadelineProto\Broadcast::class, ClassFinder::RECURSIVE_MODE) as $class) {
                    \fwrite($handle, "use $class;\n");
                }

                \fwrite($handle, "\n/** @psalm-suppress PossiblyNullReference */\nabstract class {$namespace}\n{\nprotected APIWrapper \$wrapper;\n");
                foreach ($this->TL->getMethodNamespaces() as $namespace) {
                    $namespaceInterface = '\\danog\\MadelineProto\\Namespace\\'.\ucfirst($namespace);
                    \fwrite($handle, '/** @var '.$namespaceInterface.' $'.$namespace." */\n");
                    \fwrite($handle, 'public $'.$namespace.";\n");
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
            foreach ($methods as $contents) {
                \fwrite($handle, $contents);
            }
            \fwrite($handle, "}\n");
        }
        \fclose($handle);
    }

    private function typeToStr(ReflectionType $type): string
    {
        $new = '';
        if ($type instanceof ReflectionNamedType) {
            if ($type->allowsNull() && $type->getName() !== 'mixed' && $type->getName() !== 'null') {
                $new .= '?';
            }
            if (!$type->isBuiltin()) {
                $new .= '\\';
            };
            $new .= $type->getName() === 'self' ? $this->reflectionClasses['API'] : $type->getName();
        } elseif ($type instanceof ReflectionUnionType) {
            return \implode('|', \array_map($this->typeToStr(...), $type->getTypes()));
        }
        return $new;
    }
}
