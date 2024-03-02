<?php

declare(strict_types=1);

/**
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

namespace danog\MadelineProto\TL;

use danog\MadelineProto\Settings\TLSchema;

/**
 * @internal
 */
final class Builder
{
    /**
     * TL instance.
     */
    private TL $TL;
    private readonly array $byType;
    private readonly array $idByPredicate;
    private readonly array $typeByPredicate;
    private array $needConstructors = [];
    private array $needVector = [];
    private const RECURSIVE_TYPES = [
        'InputPeer',
        'RichText',
        'PageBlock',
    ];
    private $output;
    public function __construct(
        TLSchema $settings,
        /**
         * Output file.
         */
        string $output,
        /**
         * Output namespace.
         */
        private string $namespace,
    ) {
        $this->output = fopen($output, 'w');
        $this->TL = new TL();
        $this->TL->init($settings);

        $byType = [];
        $idByPredicate = ['vector' => var_export(hex2bin('1cb5c415'), true)];
        foreach ($this->TL->getConstructors()->by_id as $id => $constructor) {
            $byType[$constructor['type']][$id]= $constructor;
            $idByPredicate[$constructor['predicate']] = var_export($id, true);
            $typeByPredicate[$constructor['predicate']] = $constructor['type'];
        }
        $this->byType = $byType;
        $this->idByPredicate = $idByPredicate;
        $this->typeByPredicate = $typeByPredicate;
    }
    private static function escapeConstructorName(array $constructor): string
    {
        return str_replace(['.', ' '], '___', $constructor['predicate']).(isset($constructor['layer']) ?'_'.$constructor['layer'] : '');
    }
    private static function escapeTypeName(string $name): string
    {
        return str_replace(['.', ' '], '___', $name);
    }
    private array $methodsCalled = [];
    private array $methodsCreated = [];
    private function methodCall(string $method): string {
        $this->methodsCalled[$method] = true;
        return $this->methodsCreated[$method]
            ? "\$this->$method(\$stream)"
            : "self::$method(\$stream)";
    }
    private function buildParam(array $param): string
    {
        ['type' => $type] = $param;
        if (isset($param['subtype'])) {
            $this->needVector[$param['subtype']] = true;
            $type = "array_of_{$param['subtype']}";
        }
        if (($param['name'] ?? null) === 'random_bytes') {
            $type = $param['name'];
        }
        return match ($type) {
            '#' => "unpack('V', stream_get_contents(\$stream, 4))[1]",
            'int' => "unpack('l', stream_get_contents(\$stream, 4))[1]",
            'long' => "unpack('q', stream_get_contents(\$stream, 8))[1]",
            'double' => "unpack('d', stream_get_contents(\$stream, 8))[1]",
            'Bool' => 'match (stream_get_contents($stream, 4)) {'.
                $this->idByPredicate['boolTrue'].' => true,'.
                $this->idByPredicate['boolFalse'].' => false, default => '.$this->methodCall('err').' }',
            'strlong' => 'stream_get_contents($stream, 8)',
            'int128' => 'stream_get_contents($stream, 16)',
            'int256' => 'stream_get_contents($stream, 32)',
            'int512' => 'stream_get_contents($stream, 64)',
            'string', 'bytes', 'waveform', 'random_bytes' => 
                $this->methodCall("deserialize_$type"),
            default => \in_array($type, self::RECURSIVE_TYPES, true) || isset($param['subtype'])
                ? $this->methodCall("deserialize_type_{$this->escapeTypeName($type)}")
                : $this->buildTypes($this->byType[$type], $type)
        };
    }
    private function buildConstructorShort(string $predicate, array $params = []): string
    {
        if ($predicate === 'dataJSON') {
            return 'json_decode('.$this->buildParam(['type' => 'string']).', true, 512, \\JSON_THROW_ON_ERROR)';
        }
        if ($predicate === 'jsonNull') {
            return 'null';
        }
        $superBare = $this->typeByPredicate[$predicate] === 'JSONValue'
            || $this->typeByPredicate[$predicate] === 'Peer';
            
        $result = '';
        if (!$superBare) {
            $result .= "[\n";
            $result .= "'_' => '$predicate',\n";
        }
        foreach ($params as $param) {
            $name = $param['name'];

            if ($predicate === 'photoStrippedSize'
                && $name === 'bytes'
            ) {
                $code = $this->buildParam(['type' => 'string']);
                $code = "new Types\\Bytes(Tools::inflateStripped($code))";
                $name = 'inflated';
            } else {
                $code = $this->buildParam($param);
            }

            if ($superBare) {
                $result .= $code;
            } else {
                $result .= var_export($name, true)." => $code,\n";
            }
        }
        if (!$superBare) {
            $result .= ']';
        }
        if ($predicate === 'peerChat') {
            $result = "-$result";
        } elseif ($predicate === 'peerChannel') {
            $result = "-1000000000000 - $result";
        }
        return $result;
    }
    private function buildConstructor(string $predicate, array $params, array $flags): string
    {
        if (!$flags) {
            return "return {$this->buildConstructorShort($predicate, $params)};";
        }
        $result = "\$tmp = ['_' => '$predicate'];\n";
        $flagNames = [];
        foreach ($flags as ['flag' => $flag]) {
            $flagNames[$flag] = true;
        }
        foreach ($params as $param) {
            $name = $param['name'];
            if (!isset($param['pow'])) {
                $code = $this->buildParam($param);

                if (isset($flagNames[$name])) {
                    $result .= "\$$name = $code;\n";
                } else {
                    $result .= "\$tmp['$name'] = $code;\n";
                }
                continue;
            }
            $flag = "(\${$param['flag']} & {$param['pow']}) !== 0";
            if ($param['type'] === 'true') {
                $result .= "\$tmp['$name'] = $flag;\n";
                continue;
            }
            $code = $this->buildParam($param);
            $result .= "if ($flag) \$tmp['$name'] = $code;\n";
        }
        return "$result\nreturn \$tmp;";
    }
    private function buildTypes(array $constructors, ?string $type = null): string
    {
        $typeMethod = $type ? "_type_".self::escapeTypeName($type) : '';
        $result = "match (stream_get_contents(\$stream, 4)) {\n";
        foreach ($constructors as $id => $constructor) {
            [
                'predicate' => $name,
                'flags' => $flags,
                'params' => $params
            ] = $constructor;
            if ($name === 'gzip_packed') {
                continue;
            }
            if ($name === 'jsonObjectValue') {
                continue;
            }
            $nameEscaped = self::escapeConstructorName($constructor);
            if (!$flags) {
                $params = $this->buildConstructorShort($name, $params);
                $result .= var_export($id, true)." => $params,\n";
            } else {
                $this->needConstructors[$name] = true;
                $result .= var_export($id, true)." => ".$this->methodCall("deserialize_$nameEscaped").",\n";
            }
        }
        $result .= $this->idByPredicate['gzip_packed']." => ".$this->methodCall("deserialize$typeMethod", 'self::gzdecode($stream)').",\n";
        $result .= "default => self::err(\$stream)\n";
        return $result."}\n";
    }
    private function buildVector(string $type, string $body): void
    {
        $this->m("deserialize_type_array_of_{$this->escapeTypeName($type)}", '
            $stream = match(stream_get_contents(\$stream, 4)) {    
                '.$this->idByPredicate['vector'].' => $stream,    
                '.$this->idByPredicate['gzip_packed'].' => self::gzdecode_vector($stream)
            };
            $result = [];
            for ($x = unpack("V", stream_get_contents($stream, 4))[1]; $x > 0; --$x) {    
                $result []= '.$body.'
            }
            return $result;    
        ', 'array', static: $type === 'JSONValue');
    }
    private function w(string $data): void {
        fwrite($this->output, $data);
    }
    public function m(string $methodName, string $body, string $returnType = 'mixed', bool $public = false, bool $static = true): void {
        $this->methodsCreated[$methodName] = $static;
        $public = $public ? 'public' : 'private';
        $static = $static ? 'static' : '';
        $this->w("    $public $static function $methodName(mixed \$stream): $returnType {\n{$body}\n    }\n");
    }
    public function build(): void
    {
        $this->w("<?php namespace {$this->namespace};\n/** @internal Autogenerated using tools/TL/Builder.php */\nfinal class TLParser {\n");

        $this->m('err', '
            fseek($stream, -4, SEEK_CUR);
            throw new AssertionError("Unexpected ID ".bin2hex(fread($stream, 4)));
        ', 'never');

        $this->m("gzdecode", "
            \$res = fopen('php://memory', 'rw+b');
            fwrite(\$res, gzdecode(self::deserialize_string(\$stream)));
            rewind(\$res);
            return \$res;
        ");

        $this->m('gzdecode_vector', "
            \$res = fopen('php://memory', 'rw+b');
            fwrite(\$res, gzdecode(self::deserialize_string(\$stream)));
            rewind(\$res);
            return match (stream_get_contents(\$stream, 4)) {
                {$this->idByPredicate['vector']} => \$stream,
                default => self::err(\$stream)
            };
        ");

        $block_str = '
            $l = \ord(stream_get_contents($stream, 1));
            if ($l > 254) {
                throw new Exception(Lang::$current_lang["length_too_big"]);
            }
            if ($l === 254) {
                $l = unpack("V", stream_get_contents($stream, 3).\chr(0))[1];
                $x = stream_get_contents($stream, $l);
                $resto = (-$l) % 4;
                $resto = $resto < 0 ? $resto + 4 : $resto;
                if ($resto > 0) {
                    stream_get_contents($stream, $resto);
                }
            } else {
                $x = $l ? stream_get_contents($stream, $l) : "";
                $resto = (-$l+1) % 4;
                $resto = $resto < 0 ? $resto + 4 : $resto;
                if ($resto > 0) {
                    stream_get_contents($stream, $resto);
                }
            }'."\n";

        $this->m("deserialize_bytes", "
            $block_str
            return new Types\Bytes(\$x);
        ");
        $this->m("deserialize_string", "
            $block_str
            return \$x;
        ");
        $this->m("deserialize_waveform", "
            $block_str
            return TL::extractWaveform(\$x);
        ");

        $this->m('deserialize_random_bytes', '
            $l = \ord(stream_get_contents($stream, 1));
            if ($l > 254) {
                throw new Exception(Lang::$current_lang["length_too_big"]);
            }
            if ($l === 254) {
                $l = unpack("V", stream_get_contents($stream, 3).\chr(0))[1];
                if ($l < 15) {
                    throw new SecurityException("Random_bytes is too small!");
                }
            } else {
                if ($l < 15) {
                    throw new SecurityException("Random_bytes is too small!");
                }
                $l += 1;
            }
            $resto = (-$l) % 4;
            $resto = $resto < 0 ? $resto + 4 : $resto;
            if ($resto > 0) {
                $l += $resto;
            }
            stream_get_contents($stream, $l);
        ', 'void');

        $this->m('deserialize_type_array_of_JSONObjectValue', '
            $stream = match(stream_get_contents($stream, 4)) {
                '.$this->idByPredicate["vector"].' => $stream,
                '.$this->idByPredicate["gzip_packed"].' => self::gzdecode_vector($stream)
            };
            $result = [];
            for ($x = unpack("V", stream_get_contents($stream, 4))[1]; $x > 0; --$x) {
                $result['.$this->buildParam(['type' => 'string']).'] = '.$this->buildParam(['type' => 'JSONValue']).';
            }
            return $result;
        ', 'array');

        $initial_constructors = array_filter(
            $this->TL->getConstructors()->by_id,
            fn (array $arr) => $arr['type'] === 'Update'
                || $arr['predicate'] === 'rpc_result'
                || !$arr['encrypted']
        );

        $this->m("deserialize", "return {$this->buildTypes($initial_constructors)};", 'mixed', true, static: false);

        foreach ($this->TL->getConstructors()->by_id as $id => $constructor) {
            ['predicate' => $name, 'flags' => $flags, 'params' => $params, 'type' => $type] = $constructor;
            if ($name === 'jsonObjectValue') {
                continue;
            }
            if ($name === 'dataJSON') {
                continue;
            }
            if ($type === 'JSONValue') {
                continue;
            }
            if ($name === 'gzip_packed') {
                continue;
            }
            $nameEscaped = self::escapeConstructorName($constructor);
            $this->m("deserialize_$nameEscaped", $this->buildConstructor($name, $params, $flags));
        }

        foreach ($this->byType as $type => $constructors) {
            if ($type === 'JSONObjectValue') {
                continue;
            }
            $this->m(
                "deserialize_type_{$this->escapeTypeName($type)}", 
                "return {$this->buildTypes($constructors, $type)};",
                static: $type === 'JSONValue'
            );

            if (isset($this->needVector[$type])) {
                $this->buildVector($type, $this->buildTypes($constructors, "array_of_$type"));
            }
        }
        foreach (['int', 'long', 'double', 'strlong'] as $type) {
            $this->buildVector($type, $this->buildParam(['type' => $type]));
        }
        $this->w("}\n");
    }
}
