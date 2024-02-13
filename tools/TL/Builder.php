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
    private array $needConstructors = [];
    private array $needVector = [];
    private const RECURSIVE_TYPES = [
        'InputPeer',
        'RichText',
        'PageBlock',
    ];
    public function __construct(
        TLSchema $settings,
        /**
         * Output file.
         */
        private string $output,
        /**
         * Output namespace.
         */
        private string $namespace,
    ) {
        $this->TL = new TL();
        $this->TL->init($settings);

        $byType = [];
        $idByPredicate = ['vector' => var_export(hex2bin('1cb5c415'), true)];
        foreach ($this->TL->getConstructors()->by_id as $id => $constructor) {
            $byType[$constructor['type']][$id]= $constructor;
            $idByPredicate[$constructor['predicate']] = var_export($id, true);
        }
        $this->byType = $byType;
        $this->idByPredicate = $idByPredicate;
    }
    private static function escapeConstructorName(array $constructor): string
    {
        return str_replace(['.', ' '], '___', $constructor['predicate']).(isset($constructor['layer']) ?'_'.$constructor['layer'] : '');
    }
    private static function escapeTypeName(string $name): string
    {
        return str_replace(['.', ' '], '___', $name);
    }
    private function buildParam(array $param): string
    {
        ['type' => $type] = $param;
        if (isset($param['subtype'])) {
            $this->needVector[$param['subtype']] = true;
            $type = "array_of_{$param['subtype']}";
        }
        return match ($type) {
            '#' => "unpack('V', stream_get_contents(\$stream, 4))[1]",
            'int' => "unpack('l', stream_get_contents(\$stream, 4))[1]",
            'long' => "unpack('q', stream_get_contents(\$stream, 8))[1]",
            'double' => "unpack('d', stream_get_contents(\$stream, 8))[1]",
            'Bool' => 'match (stream_get_contents($stream, 4)) {'.
                $this->idByPredicate['boolTrue'].' => true,'.
                $this->idByPredicate['boolFalse'].' => false, default => self::err($stream) }',
            'strlong' => 'stream_get_contents($stream, 8)',
            'int128' => 'stream_get_contents($stream, 16)',
            'int256' => 'stream_get_contents($stream, 32)',
            'int512' => 'stream_get_contents($stream, 64)',
            default => isset($this->byType[$type]) && !\in_array($type, self::RECURSIVE_TYPES, true)
                ? $this->buildTypes($this->byType[$type], $type)
                : "self::deserialize_type_{$this->escapeTypeName($type)}(\$stream)"
        };
    }
    private function buildConstructorShort(string $predicate, array $params = []): string
    {
        $result = "[\n";
        $result .= "'_' => '$predicate',\n";
        foreach ($params as $param) {
            $name = $param['name'];
            $code = $this->buildParam($param);
            $result .= var_export($name, true)." => $code,\n";
        }
        $result .= ']';
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
        $typeMethod = $type ? "deserialize_type_".self::escapeTypeName($type) : 'deserialize';
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
            $nameEscaped = self::escapeConstructorName($constructor);
            if (!$flags) {
                $params = $this->buildConstructorShort($name, $params);
                $result .= var_export($id, true)." => $params,\n";
            } else {
                $this->needConstructors[$name] = true;
                $result .= var_export($id, true)." => self::deserialize_$nameEscaped(\$stream),\n";
            }
        }
        $result .= $this->idByPredicate['gzip_packed']." => self::$typeMethod(self::gzdecode(\$stream)),\n";
        $result .= "default => self::err(\$stream)\n";
        return $result."}\n";
    }
    private function buildVector(string $type, string $body): string
    {
        $result = '';
        $result .= "private static function deserialize_type_array_of_{$this->escapeTypeName($type)}(mixed \$stream): mixed {\n";
        $result .= "\$stream = match(stream_get_contents(\$stream, 4)) {\n";
        $result .= $this->idByPredicate['vector']." => \$stream,\n";
        $result .= $this->idByPredicate['gzip_packed']." => self::gzdecode_vector(\$stream)\n";
        $result .= "};\n";
        $result .= "\$result = [];\n";
        $result .= "for (\$x = unpack('V', stream_get_contents(\$stream, 4))[1]; \$x > 0; \$x--) {\n";
        $result .= "\$result []= {$body};";
        $result .= "}\n";
        $result .= "return \$result;\n";
        $result .= "}\n";
        return $result;
    }
    public function build(): void
    {
        $f = fopen($this->output, 'w');
        fwrite($f, "<?php namespace {$this->namespace};\n/** @internal Autogenerated using tools/TL/Builder.php */\nfinal class TLParser {\n");

        fwrite($f, 'private static function err(mixed $stream): never {
            fseek($stream, -4, SEEK_CUR);
            throw new AssertionError("Unexpected ID ".bin2hex(fread($stream, 4)));
        }'."\n");

        fwrite($f, "private static function gzdecode(mixed \$stream): mixed {
            \$res = fopen('php://memory', 'rw+b');
            fwrite(\$res, gzdecode(self::deserialize_type_bytes(\$stream)));
            rewind(\$res);
            return \$res;
        }\n");

        fwrite($f, "private static function gzdecode_vector(mixed \$stream): mixed {
            \$res = fopen('php://memory', 'rw+b');
            fwrite(\$res, gzdecode(self::deserialize_type_bytes(\$stream)));
            rewind(\$res);
            return match (stream_get_contents(\$stream, 4)) {
                {$this->idByPredicate['vector']} => \$stream,
                default => self::err(\$stream)
            };
        }\n");

        $block_str = '
            $l = \ord(stream_get_contents($stream, 1));
            if ($l > 254) {
                throw new Exception(Lang::$current_lang["length_too_big"]);
            }
            if ($l === 254) {
                $long_len = unpack("V", stream_get_contents($stream, 3).\chr(0))[1];
                $x = stream_get_contents($stream, $long_len);
                $resto = Tools::posmod(-$long_len, 4);
                if ($resto > 0) {
                    stream_get_contents($stream, $resto);
                }
            } else {
                $x = $l ? stream_get_contents($stream, $l) : "";
                $resto = Tools::posmod(-($l + 1), 4);
                if ($resto > 0) {
                    stream_get_contents($stream, $resto);
                }
            }'."\n";
        fwrite($f, "private static function deserialize_type_bytes(mixed \$stream): mixed {
            $block_str
            return new Types\Bytes(\$x);
        }\n");
        fwrite($f, "private static function deserialize_type_string(mixed \$stream): mixed {
            $block_str
            return \$x;
        }\n");
        fwrite($f, "private static function deserialize_type_waveform(mixed \$stream): mixed {
            $block_str
            return TL::extractWaveform(\$x);
        }\n");

        fwrite($f, "final public function deserialize(mixed \$stream): mixed {\n");
        fwrite($f, "return {$this->buildTypes($this->TL->getConstructors()->by_id)};");
        fwrite($f, "}\n");

        foreach ($this->TL->getConstructors()->by_id as $id => $constructor) {
            ['predicate' => $name, 'flags' => $flags, 'params' => $params, 'type' => $type] = $constructor;
            $nameEscaped = self::escapeConstructorName($constructor);
            fwrite($f, "private static function deserialize_$nameEscaped(mixed \$stream): array {\n");
            fwrite($f, "{$this->buildConstructor($name, $params, $flags)}\n");
            fwrite($f, "}\n");
        }

        foreach ($this->byType as $type => $constructors) {
            fwrite($f, "private static function deserialize_type_{$this->escapeTypeName($type)}(mixed \$stream): mixed {\n");
            fwrite($f, "return {$this->buildTypes($constructors, $type)};");
            fwrite($f, "}\n");

            if (isset($this->needVector[$type])) {
                fwrite($f, $this->buildVector($type, $this->buildTypes($constructors, "array_of_$type")));
            }
        }
        foreach (['int', 'long', 'double', 'strlong'] as $type) {
            fwrite($f, $this->buildVector($type, $this->buildParam(['type' => $type])));
        }
        fwrite($f, "}\n");
    }
}
