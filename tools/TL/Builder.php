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

use AssertionError;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProtoTools\MinDatabase;
use danog\MadelineProto\MTProtoTools\PeerDatabase;
use danog\MadelineProto\MTProtoTools\ReferenceDatabase;
use danog\MadelineProto\Settings\TLSchema;
use ReflectionFunction;
use Webmozart\Assert\Assert;

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
    private readonly array $constructorByPredicate;
    private readonly array $methodVectorTypes;
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
        $constructorByPredicate = [];
        foreach ($this->TL->getConstructors()->by_id as $id => $constructor) {
            foreach ($constructor['params'] as &$param) {
                if ($constructor['predicate'] === 'photoStrippedSize'
                    && $param['name'] === 'bytes'
                ) {
                    $param['name'] = 'inflated';
                    $param['type'] = 'inflated';
                }

                if ($param['name'] === 'random_bytes') {
                    $param['type'] = 'random_bytes';
                }

                if (isset($param['subtype'])) {
                    $param['type'] = match ($param['type']) {
                        'Vector t' => "Vector<{$param['subtype']}>",
                        'vector' => "vector<{$param['subtype']}>",
                    };
                }
            }

            if (isset($constructor['layer'])) {
                continue;
            }
            $constructor['id'] = $id;

            $constructorByPredicate[$constructor['predicate']] = $constructor;
            $idByPredicate[$constructor['predicate']] = var_export($id, true);
            $typeByPredicate[$constructor['predicate']] = $constructor['type'];
            $byType[$constructor['type']][$id]= $constructor;
        }
        $this->idByPredicate = $idByPredicate;
        $this->typeByPredicate = $typeByPredicate;
        $this->constructorByPredicate = $constructorByPredicate;

        $methodConstructors = [];
        $methodVectorTypes = [];
        foreach ($this->TL->getMethods()->by_id as $c) {
            ['type' => $type, 'method' => $name, 'encrypted' => $encrypted] = $c;
            if (!$encrypted) {
                continue;
            }
            // TODO
            if ($type === 'X') {
                continue;
            }
            if (isset($c['subtype'])) {
                $methodVectorTypes[$name] = match ($c['type']) {
                    'Vector t' => "Vector<{$c['subtype']}>",
                    'vector' => "vector<{$c['subtype']}>",
                };
                continue;
            }
            $methodConstructors = array_merge($methodConstructors, $byType[$type]);
        }

        $byType['MethodResult'] = $methodConstructors;
        $this->methodVectorTypes = $methodVectorTypes;
        $this->byType = $byType;
    }

    private static function escapeConstructorName(array $constructor): string
    {
        return str_replace(['.', ' '], '___', $constructor['predicate']);
    }
    private static function escapeTypeName(string $name): string
    {
        return str_replace(['.', ' '], '___', $name);
    }
    private function needFullConstructor(string $predicate): bool
    {
        if (isset($this->TL->beforeConstructorDeserialization[$predicate])
            || isset($this->TL->afterConstructorDeserialization[$predicate])) {
            return true;
        }
        return false;
    }
    private static function methodFromClosure(ReflectionFunction $closure): string
    {
        $refl = new ReflectionFunction($closure);
        return match ($refl->getClosureThis()::class) {
            PeerDatabase::class => '$this->peerDatabase',
            MinDatabase::class => '$this->minDatabase?',
            ReferenceDatabase::class => '$this->referenceDatabase?',
            MTProto::class => '$this->API',
        }."->".$refl->getName();
    }

    private function buildTypes(array $constructors, string $type): string
    {
        $typeMethod = "_type_".self::escapeTypeName($type);
        $result = "match (stream_get_contents(\$stream, 4)) {\n";
        foreach ($constructors as ['predicate' => $predicate, 'id' => $id]) {
            if ($predicate === 'gzip_packed') {
                continue;
            }
            if ($predicate === 'jsonObjectValue') {
                throw new AssertionError("Impossible!");
            }
            $result .= var_export($id, true)." => ";
            $result .= $this->buildConstructor($predicate);
            $result .= ",\n";
        }
        if ($type === 'MethodResult') {
            $result .= $this->idByPredicate['gzip_packed']." => ".$this->methodCall(
                "deserialize$typeMethod",
                'self::gzdecode($stream), $method'
            ).",\n";
            $result .= $this->idByPredicate['vector']." => match (\$method) {\n";
            foreach ($this->methodVectorTypes as $method => $type) {
                $result .= var_export($method, true) . ' => '.$this->buildType($type).",\n";
            }
            $result .= "},\n";
        } else {
            $result .= $this->idByPredicate['gzip_packed']." => ".$this->methodCall(
                "deserialize$typeMethod",
                'self::gzdecode($stream)'
            ).",\n";
        }
        $result .= "default => self::err(\$stream)\n";
        return $result."}\n";
    }
    private array $createdConstructors = [];
    public function buildConstructor(string $predicate): string
    {
        $constructor = $this->constructorByPredicate[$predicate];
        Assert::notFalse($constructor, "Missing constructor $predicate");
        [
            'flags' => $flags,
            'params' => $params,
        ] = $constructor;

        if ($predicate === 'rpc_result') {
            $result = "\$tmp = ['_' => '$predicate', 'req_msg_id' => \$id = {$this->buildType('long')}];\n";
            $result .= '$message = $this->connection->outgoing_messages[$id];
            $method = $message->constructor;
            if (isset($this->beforeMethodResponseDeserialization[$method])) {
                foreach ($this->beforeMethodResponseDeserialization[$method] as $callback) {
                    $callback($method);
                }
            }
            $tmp["result"] = '.$this->buildType('MethodResult').';
            if (isset($this->afterMethodResponseDeserialization[$method])) {
                foreach ($this->afterMethodResponseDeserialization[$method] as $callback) {
                    $callback($tmp);
                }
            }
            return $tmp;
            ';
        } elseif ($flags) {
            $result = $this->buildConstructorFull($predicate, $params, $flags);
        } else {
            $result = $this->buildConstructorShort($predicate, $params, $flags);
            if (!$this->needFullConstructor($predicate)) {
                return $result;
            }
            $result = "\$tmp = $result";
        }

        $pre = '';
        foreach ($this->TL->beforeConstructorDeserialization[$predicate] ?? [] as $closure) {
            $pre .= self::methodFromClosure($closure)."('$predicate');\n";
        }
        $result = $pre.$result;
        foreach ($this->TL->afterConstructorDeserialization[$predicate] ?? [] as $closure) {
            $result .= self::methodFromClosure($closure)."(\$tmp);\n";
        }

        $nameEscaped = self::escapeConstructorName($constructor);
        if (!isset($this->createdConstructors[$predicate])) {
            $this->createdConstructors[$predicate] = true;
            $this->m("deserialize_$nameEscaped", $result);
        }

        return $this->methodCall("deserialize_$nameEscaped");
    }
    private function buildConstructorFull(string $predicate, array $params, array $flags): string
    {
        $result = "\$tmp = ['_' => '$predicate'];\n";
        $flagNames = [];
        foreach ($flags as ['flag' => $flag]) {
            $flagNames[$flag] = true;
        }
        foreach ($params as $param) {
            $name = $param['name'];
            if (!isset($param['pow'])) {
                $code = $this->buildType($param['type']);

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
            $code = $this->buildType($param['type']);
            $result .= "if ($flag) \$tmp['$name'] = $code;\n";
        }
        return "$result\nreturn \$tmp;";
    }

    private function buildConstructorShort(string $predicate, array $params = []): string
    {
        if ($predicate === 'dataJSON') {
            return 'json_decode('.$this->buildType('string').', true, 512, \\JSON_THROW_ON_ERROR)';
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
            $code = $this->buildType($param['type']);

            if ($superBare) {
                $result .= $code;
            } else {
                $result .= var_export($param['name'], true)." => $code,\n";
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

    private array $createdVectors = [];
    private function buildVector(string $type, bool $bare, ?string $payload = null): string
    {
        if (!isset($this->createdVectors[$type])) {
            $this->createdVectors[$type] = true;
            if ($type === 'JSONObjectValue') {
                $payload = '$result['.$this->buildType('string').'] = '.$this->buildType('JSONValue');
            } elseif (isset($this->byType[$type])) {
                $payload = '$result []= '.$this->buildTypes($this->byType[$type], $type);
            } elseif ($payload === null) {
                if ($type === '%MTMessage') {
                    $type = 'MTmessage';
                }
                $payload = '$result []= '.$this->buildConstructor($type);
            }
            $this->m("deserialize_type_array_of_{$this->escapeTypeName($type)}", '
                $result = [];
                for ($x = unpack("V", stream_get_contents($stream, 4))[1]; $x > 0; --$x) {    
                    '.$payload.';
                }
                return $result;    
            ', 'array', static: $type === 'JSONValue');
        }
        return $this->methodCall(
            "deserialize_type_array_of_{$this->escapeTypeName($type)}",
            $bare ? '$stream' : 'match(stream_get_contents($stream, 4)) {    
                '.$this->idByPredicate['vector'].' => $stream,    
                '.$this->idByPredicate['gzip_packed'].' => self::gzdecode_vector($stream)
            }'
        );
    }

    private array $createdTypes = ['Object' => true];
    private array $typeStack = [];
    private function buildType(string $type): string
    {
        if (str_starts_with($type, 'Vector<')) {
            return $this->buildVector(str_replace(['Vector<', '>'], '', $type), false);
        }
        if (str_starts_with($type, 'vector<')) {
            return $this->buildVector(str_replace(['vector<', '>'], '', $type), true);
        }
        $tmp = match ($type) {
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
            'inflated' =>
                'new Types\Bytes(Tools::inflateStripped('.$this->methodCall("deserialize_string").'))',
            default => null
        };
        if ($tmp !== null) {
            return $tmp;
        }

        if (!isset($this->createdTypes[$type])) {
            $this->createdTypes[$type] = true;

            $this->m(
                "deserialize_type_{$this->escapeTypeName($type)}",
                "return {$this->buildTypes($this->byType[$type], $type)};",
                static: $type === 'JSONValue'
            );
        }

        $had = array_search($type, $this->typeStack) !== false;
        $this->typeStack []= $type;
        try {
            if (!$had) {
                return $this->buildTypes($this->byType[$type], $type);
            }
            return $this->methodCall("deserialize_type_{$this->escapeTypeName($type)}");
        } finally {
            array_pop($this->typeStack);
        }
    }

    private array $methodsCreated = [];
    private function methodCall(string $method, string $stream = '$stream'): string
    {
        return ($this->methodsCreated[$method] ?? true)
            ? "\$this->$method($stream)"
            : "self::$method($stream)";
    }

    public function m(string $methodName, string $body, string $returnType = 'mixed', bool $public = false, bool $static = false, string $extraArg = ''): void
    {
        if (isset($this->methodsCreated[$methodName])) {
            throw new AssertionError("Already created $methodName!");
        }

        $this->methodsCreated[$methodName] = $static;
        $public = $public ? 'public' : 'private';
        $static = $static ? 'static' : '';
        $this->w("    $public $static function $methodName(mixed \$stream$extraArg): $returnType {\n{$body}\n    }\n");
    }
    private function w(string $data): void
    {
        fwrite($this->output, $data);
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

        foreach (['int', 'long', 'double', 'strlong', 'string', 'bytes'] as $type) {
            $this->buildVector($type, false, '$result []= '.$this->buildType($type));
        }

        $initial_constructors = array_filter(
            $this->constructorByPredicate,
            static fn (array $arr) => (
                $arr['type'] === 'Update'
                || $arr['predicate'] === 'rpc_result'
                || !$arr['encrypted']
            ) && (
                $arr['predicate'] !== 'rpc_error'
                && $arr['predicate'] !== 'MTmessage'
            )
        );

        $this->m("deserialize_type_Object", "return {$this->buildTypes($initial_constructors, 'Object')};", 'mixed', true, static: false);

        $this->w("}\n");
    }
}
