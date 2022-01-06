<?php

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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Promise;
use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\TL\TL;

class AnnotationsBuilder
{
    /**
     * TL instance.
     */
    private TL $TL;
    /**
     * Constructor.
     *
     * @param Logger $logger Logger
     * @param array $settings Settings
     * @param string $basedir Output directory
     * @param class-string $API API class
     * @param class-string $MTProto MTProto class
     * @param string $namespace Output namespace
     */
    public function __construct(Logger $logger, array $settings, private string $basedir, private string $API, private string $MTProto, private string $namespace)
    {
        $this->basedir .= "/".\str_replace('\\', '/', $namespace).'/';
        if (!\file_exists($this->basedir)) {
            \mkdir($this->basedir);
        }
        /** @psalm-suppress InvalidArgument */
        $this->TL = new TL(new class($logger) {
            public Logger $logger;
            public function __construct(Logger $logger)
            {
                $this->logger = $logger;
            }
        });
        $tlSchema = new TLSchema;
        $tlSchema->mergeArray($settings);
        $this->TL->init($tlSchema);
    }
    public function run(): void
    {
        \danog\MadelineProto\Logger::log('Generating annotations...', \danog\MadelineProto\Logger::NOTICE);
        $this->createInternalClasses();
    }
    /**
     * Create internalDoc.
     *
     * @return void
     */
    private function createInternalClasses(): void
    {
        \danog\MadelineProto\Logger::log('Creating internal classes...', \danog\MadelineProto\Logger::NOTICE);
        $internalDoc = [];
        foreach ($this->TL->getMethods()->by_id as $id => $data) {
            if (!\strpos($data['method'], '.')) {
                continue;
            }
            list($namespace, $method) = \explode('.', $data['method']);
            if (!\in_array($namespace, $this->TL->getMethodNamespaces())) {
                continue;
            }
            $internalDoc[$namespace][$method]['title'] = \str_replace(['](../', '.md'], ['](https://docs.madelineproto.xyz/API_docs/', '.html'], Lang::$lang['en']["method_{$data['method']}"] ?? '');
            $type = \str_ireplace(['vector<', '>'], [' of ', '[]'], $data['type']);
            foreach ($data['params'] as $param) {
                if (\in_array($param['name'], ['flags', 'random_id', 'random_bytes'])) {
                    continue;
                }
                if ($param['name'] === 'data' && $type === 'messages.SentEncryptedMessage') {
                    $param['name'] = 'message';
                    $param['type'] = 'DecryptedMessage';
                }
                if ($param['name'] === 'chat_id' && $data['method'] !== 'messages.discardEncryption') {
                    $param['type'] = 'InputPeer';
                }
                if ($param['name'] === 'hash' && $param['type'] === 'int') {
                    $param['pow'] = 'hi';
                    $param['type'] = 'Vector t';
                    $param['subtype'] = 'int';
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
                $opt = $param['pow'] ?? false ? 'Optional: ' : '';
                $internalDoc[$namespace][$method]['attr'][$param['name']] = ['type' => $ptype, 'description' => \str_replace(['](../', '.md'], ['](https://docs.madelineproto.xyz/API_docs/', '.html'], $opt.(Lang::$lang['en']["method_{$data['method']}_param_{$param['name']}_type_{$param['type']}"] ?? ''))];
            }
            if ($type === 'Bool') {
                $type = \strtolower($type);
            }
            $internalDoc[$namespace][$method]['return'] = $type;
        }
        $namespaces = \array_keys($internalDoc);
        $class = new \ReflectionClass($this->MTProto);
        $methods = $class->getMethods((\ReflectionMethod::IS_STATIC & \ReflectionMethod::IS_PUBLIC) | \ReflectionMethod::IS_PUBLIC);
        $class = new \ReflectionClass(Tools::class);
        $methods = \array_merge($methods, $class->getMethods((\ReflectionMethod::IS_STATIC & \ReflectionMethod::IS_PUBLIC) | \ReflectionMethod::IS_PUBLIC));
        foreach ($methods as $key => $method) {
            $name = $method->getName();
            if ($method == 'methodCallAsyncRead') {
                unset($methods[\array_search('methodCall', $methods)]);
            } elseif (\strpos($name, '__') === 0) {
                unset($methods[$key]);
            } elseif (\stripos($name, 'async') !== false) {
                if (\strpos($name, '_async') !== false) {
                    unset($methods[\array_search(\str_ireplace('_async', '', $method), $methods)]);
                } else {
                    unset($methods[\array_search(\str_ireplace('async', '', $method), $methods)]);
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
            if ($name === 'fetchserializableobject' || $name === 'initDb' || $name === 'deinitDb') {
                continue;
            }
            if (\strpos($method->getDocComment() ?? '', '@internal') !== false) {
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
            $doc = 'public function ';
            $doc .= $name;
            $doc .= '(';
            $paramList = '';
            $hasVariadic = false;
            foreach ($method->getParameters() as $param) {
                if ($param->allowsNull()) {
                    //$doc .= '?';
                }
                if ($type = $param->getType()) {
                    if ($type->allowsNull()) {
                        $doc .= '?';
                    }
                    if (!$type->isBuiltin()) {
                        $doc .= '\\';
                    }
                    $doc .= $type->getName();
                    $doc .= ' ';
                } else {
                    Logger::log($name.'.'.$param->getName()." has no type!", Logger::WARNING);
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
                    $hasVariadic = true;
                    $paramList .= '...';
                }
                $paramList .= '$'.$param->getName().', ';
            }
            $hasReturnValue = ($type = $method->getReturnType()) && !\in_array($type->getName(), [\Generator::class, Promise::class]);
            if (!$hasVariadic && !$static && !$hasReturnValue) {
                $paramList .= '$extra, ';
                $doc .= 'array $extra = []';
            }
            $doc = \rtrim($doc, ', ');
            $paramList = \rtrim($paramList, ', ');
            $doc .= ")";
            $async = true;
            if ($hasReturnValue && $static) {
                $doc .= ': ';
                if ($type->allowsNull()) {
                    $doc .= '?';
                }
                if (!$type->isBuiltin()) {
                    $doc .= '\\';
                }
                $doc .= $type->getName() === 'self' ? $this->API : $type->getName();
                $async = false;
            }
            if ($method->getDeclaringClass()->getName() == Tools::class) {
                $async = false;
            }
            $finalParamList = $hasVariadic ? "Tools::arr({$paramList})" : "[{$paramList}]";
            $ret = $type && \in_array($type->getName(), ['self', 'void']) ? '' : 'return';
            $doc .= "\n{\n";
            if ($async) {
                $doc .= "    {$ret} \$this->__call(__FUNCTION__, {$finalParamList});\n";
            /*} elseif (!$static) {
                $doc .= "    {$ret} \$this->API->{$name}({$paramList});\n";
            */
            } else {
                $doc .= "    {$ret} \\".$method->getDeclaringClass()->getName()."::".$name."({$paramList});\n";
            }
            if (!$ret && $type->getName() === 'self') {
                $doc .= "    return \$this;\n";
            }
            $doc .= "}\n";
            if (!$method->getDocComment()) {
                Logger::log("{$name} has no PHPDOC!", Logger::FATAL_ERROR);
            }
            if (!$type) {
                Logger::log("{$name} has no return type!", Logger::FATAL_ERROR);
            }
            $promise = '\\'.Promise::class;
            $phpdoc = $method->getDocComment() ?? '';
            $phpdoc = \str_replace("@return \\Generator", "@return $promise", $phpdoc);
            $phpdoc = \str_replace("@return \\Promise", "@return $promise", $phpdoc);
            $phpdoc = \str_replace("@return Promise", "@return $promise", $phpdoc);
            if ($hasReturnValue && $async && \preg_match("/@return (.*)/", $phpdoc, $matches)) {
                $ret = $matches[1];
                $new = $ret;
                if ($type && !\str_contains($ret, '<')) {
                    $new = '';
                    if ($type->allowsNull()) {
                        $new .= '?';
                    }
                    if (!$type->isBuiltin()) {
                        $new .= '\\';
                    }
                    $new .= $type->getName() === 'self' ? $this->API : $type->getName();
                }
                $phpdoc = \str_replace("@return ".$ret, "@return mixed", $phpdoc);
                if (!\str_contains($phpdoc, '@psalm-return')) {
                    $phpdoc = \str_replace("@return ", "@psalm-return $new|$promise<$new>\n     * @return ", $phpdoc);
                }
            }
            $phpdoc = \preg_replace(
                "/@psalm-return \\\\Generator<(?:[^,]+), (?:[^,]+), (?:[^,]+), (.+)>/",
                "@psalm-return $promise<$1>",
                $phpdoc
            );
            $internalDoc['MethodDoc'][$name]['method'] = $phpdoc;
            $internalDoc['MethodDoc'][$name]['method'] .= "\n    ".\implode("\n    ", \explode("\n", $doc));
        }
        $handleNamespace = \fopen($this->basedir."/NamespaceDoc.php", 'w');
        $handleMethod = \fopen($this->basedir."/MethodDoc.php", 'w');
        foreach ([$handleMethod, $handleNamespace] as $handle) {
            \fwrite($handle, "<?php\n");
            \fwrite($handle, "/**\n");
            \fwrite($handle, " * This file is automatic generated by build_docs.php file\n");
            \fwrite($handle, " * and is used only for autocomplete in multiple IDE\n");
            \fwrite($handle, " * don't modify manually.\n");
            \fwrite($handle, " */\n\n");
            \fwrite($handle, "namespace {$this->namespace};\n");
        }
        \fwrite($handleNamespace, "abstract class NamespaceDoc extends \\danog\\MadelineProto\\APIProxy {\n");
        foreach ($this->TL->getMethodNamespaces() as $namespace) {
            \fwrite($handleNamespace, "/** @var $namespace */\npublic \$$namespace;\n");
        }
        \fwrite($handleNamespace, "}\n");

        $namespaces = \var_export($namespaces, true);
        foreach ($internalDoc as $namespace => $methods) {
            if ($namespace === 'MethodDoc') {
                $handle = $handleMethod;
                \fwrite($handle, "\nclass {$namespace} extends NamespaceDoc\n{\n");
                \fwrite($handle, "protected function cloneProxy(): self { return new self; }\n");
                \fwrite($handle, "protected function getAPINamespaces(): array { return $namespaces; }\n");
            } else {
                $handle = $handleNamespace;
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
                if (isset($properties['attr'])) {
                    \fwrite($handle, "     * Parameters: \n");
                    $longest = [0, 0, 0];
                    foreach ($properties['attr'] as $name => $param) {
                        $longest[0] = \max($longest[0], \strlen($param['type']));
                        $longest[1] = \max($longest[1], \strlen($name));
                        $longest[2] = \max($longest[2], \strlen($param['description']));
                    }
                    foreach ($properties['attr'] as $name => $param) {
                        $param['type'] = \str_pad('`'.$param['type'].'`', $longest[0] + 2);
                        $name = \str_pad('**'.$name.'**', $longest[1] + 4);
                        $param['description'] = \str_pad($param['description'], $longest[2]);
                        \fwrite($handle, "     * * {$param['type']} {$name} - {$param['description']}\n");
                    }
                    \fwrite($handle, "     * \n");
                    \fwrite($handle, "     * @param array \$params Parameters\n");
                    \fwrite($handle, "     *\n");
                }
                \fwrite($handle, "     * @return {$properties['return']}\n");
                \fwrite($handle, "     */\n");
                \fwrite($handle, "    public function {$method}(");
                if (isset($properties['attr'])) {
                    \fwrite($handle, '$params');
                }
                \fwrite($handle, ");\n");
            }
            \fwrite($handle, "}\n");
        }
        \fclose($handle);
    }
}
