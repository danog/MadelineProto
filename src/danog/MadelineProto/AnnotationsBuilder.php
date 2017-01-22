<?php
/*
 Copyright 2016 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto;

use phpDocumentor\Reflection\DocBlockFactory;

class AnnotationsBuilder
{
    use \danog\MadelineProto\TL\TL;

    public function __construct($settings)
    {
        $this->construct_TL($settings['tl_schema']);
        $this->settings = $settings;
    }

    public function mk_annotations()
    {
        \danog\MadelineProto\Logger::log('Generating annotations...');
        $this->setProperties();
        $this->createInternalClasses();
    }

    /**
     * Open file of class APIFactory
     * Insert properties
     * save the file with new content.
     */
    private function setProperties()
    {
        \danog\MadelineProto\Logger::log('Generating properties...');
        $fixture = DocBlockFactory::createInstance();
        $class = new \ReflectionClass(APIFactory::class);
        $content = file_get_contents($filename = $class->getFileName());
        foreach ($class->getProperties() as $property) {
            if ($raw_docblock = $property->getDocComment()) {
                $docblock = $fixture->create($raw_docblock);
                if ($docblock->hasTag('internal')) {
                    $content = str_replace("\n    ".$raw_docblock."\n    public $".$property->getName().';', '', $content);
                }
            }
        }
        foreach ($this->methods->method_namespace as $namespace) {
            $content = preg_replace(
                '/(class( \w+[,]?){0,}\n{\n)/',
                '${1}'.
                "    /**\n".
                "     * @internal this is a internal property generated by build_docs.php, dont change manually\n".
                "     *\n".
                "     * @var $namespace\n".
                "     */\n".
                "    public \$$namespace;\n",
                $content
            );
        }
        file_put_contents($filename, $content);
    }

    /**
     * Create file InternalDoc with all interfaces.
     */
    private function createInternalClasses()
    {
        \danog\MadelineProto\Logger::log('Creating internal classes...');
        $handle = fopen(dirname(__FILE__).'/InternalDoc.php', 'w');
        foreach ($this->methods->method as $key => $rmethod) {
            if (!strpos($rmethod, '.')) {
                continue;
            }
            list($namespace, $method) = explode('.', $rmethod);
            if (!in_array($namespace, $this->methods->method_namespace)) {
                continue;
            }
            $type = str_replace(['.', '<', '>'], ['_', '_of_', ''], $this->methods->type[$key]);
            foreach ($this->methods->params[$key] as $param) {
                if (in_array($param['name'], ['flags', 'random_id'])) {
                    continue;
                }
                $stype = 'type';
                if (isset($param['subtype'])) {
                    $stype = 'subtype';
                }
                $ptype = str_replace('.', '_', $param[$stype]);
                switch ($ptype) {
                    case 'true':
                    case 'false':
                        $ptype = 'boolean';
                }
                $internalDoc[$namespace][$method]['attr'][$param['name']] = $ptype;
            }
            if ($type == 'Bool') {
                $type = strtolower($type);
            }
            $internalDoc[$namespace][$method]['return'] = $type;
        }
        fwrite($handle, "<?php\n");
        fwrite($handle, "/**\n");
        fwrite($handle, " * This file is automatic generated by build_docs.php file\n");
        fwrite($handle, " * and is used only for autocomplete in multiple IDE\n");
        fwrite($handle, " * dont modify manually.\n");
        fwrite($handle, " */\n\n");
        fwrite($handle, "namespace danog\MadelineProto;\n");
        foreach ($internalDoc as $namespace => $methods) {
            fwrite($handle, "\ninterface $namespace\n{");
            foreach ($methods as $method => $properties) {
                fwrite($handle, "\n    /**\n");
                if (isset($properties['attr'])) {
                    fwrite($handle, "     * @param array params [\n");
                    foreach ($properties['attr'] as $name => $type) {
                        fwrite($handle, "     *               $type $name,\n");
                    }
                    fwrite($handle, "     *              ]\n");
                    fwrite($handle, "     *\n");
                }
                fwrite($handle, "     * @return {$properties['return']}\n");
                fwrite($handle, "     */\n");
                fwrite($handle, "    public function $method(");
                if (isset($properties['attr'])) {
                    fwrite($handle, 'array $params');
                }
                fwrite($handle, ");\n");
            }
            fwrite($handle, "}\n");
        }
        fclose($handle);
    }
}