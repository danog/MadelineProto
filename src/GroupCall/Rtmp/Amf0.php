<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\GroupCall\Rtmp;

use UnexpectedValueException;

/**
 * The subset of AMF0 (Action Message Format) used by the commands and metadata of RTMP.
 *
 * Arrays with string keys are objects, lists are strict arrays; {@see Amf0EcmaArray} wraps the
 * associative arrays to send as ECMA arrays (the type of onMetaData).
 *
 * @internal
 */
final class Amf0
{
    private const NUMBER = 0x00;
    private const BOOLEAN = 0x01;
    private const STRING = 0x02;
    private const OBJECT = 0x03;
    private const NULL = 0x05;
    private const UNDEFINED = 0x06;
    private const ECMA_ARRAY = 0x08;
    private const OBJECT_END = 0x09;
    private const STRICT_ARRAY = 0x0A;
    private const DATE = 0x0B;
    private const LONG_STRING = 0x0C;

    /**
     * Encode values one after the other, as the arguments of a command.
     *
     * @psalm-pure
     */
    public static function encode(mixed ...$values): string
    {
        $out = '';
        foreach ($values as $value) {
            $out .= self::encodeValue($value);
        }
        return $out;
    }

    /**
     * @psalm-pure
     */
    private static function encodeValue(mixed $value): string
    {
        if ($value === null) {
            return \chr(self::NULL);
        }
        if (\is_bool($value)) {
            return \chr(self::BOOLEAN).($value ? "\x01" : "\x00");
        }
        if (\is_int($value) || \is_float($value)) {
            return \chr(self::NUMBER).pack('E', (float) $value);
        }
        if (\is_string($value)) {
            return \strlen($value) > 0xFFFF
                ? \chr(self::LONG_STRING).pack('N', \strlen($value)).$value
                : \chr(self::STRING).pack('n', \strlen($value)).$value;
        }
        if ($value instanceof Amf0EcmaArray) {
            return \chr(self::ECMA_ARRAY).pack('N', \count($value->values)).self::encodeProperties($value->values);
        }
        if (\is_array($value)) {
            if (array_is_list($value) && $value !== []) {
                $out = \chr(self::STRICT_ARRAY).pack('N', \count($value));
                foreach ($value as $item) {
                    $out .= self::encodeValue($item);
                }
                return $out;
            }
            return \chr(self::OBJECT).self::encodeProperties($value);
        }
        throw new UnexpectedValueException('Cannot encode a '.get_debug_type($value).' in AMF0');
    }

    /**
     * @param array<array-key, mixed> $properties
     *
     * @psalm-pure
     */
    private static function encodeProperties(array $properties): string
    {
        $out = '';
        foreach ($properties as $key => $value) {
            $key = (string) $key;
            $out .= pack('n', \strlen($key)).$key.self::encodeValue($value);
        }
        return $out."\x00\x00".\chr(self::OBJECT_END);
    }

    /**
     * Decode every value of a payload.
     *
     * @return list<mixed>
     *
     * @throws UnexpectedValueException If the payload is malformed.
     */
    public static function decode(string $data): array
    {
        $offset = 0;
        $values = [];
        while ($offset < \strlen($data)) {
            $values[] = self::decodeValue($data, $offset);
        }
        return $values;
    }

    private static function decodeValue(string $data, int &$offset): mixed
    {
        $type = \ord(self::take($data, $offset, 1));
        switch ($type) {
            case self::NUMBER:
                return unpack('E', self::take($data, $offset, 8))[1];
            case self::BOOLEAN:
                return self::take($data, $offset, 1) !== "\x00";
            case self::STRING:
                return self::take($data, $offset, unpack('n', self::take($data, $offset, 2))[1]);
            case self::LONG_STRING:
                return self::take($data, $offset, unpack('N', self::take($data, $offset, 4))[1]);
            case self::NULL:
            case self::UNDEFINED:
                return null;
            case self::ECMA_ARRAY:
                self::take($data, $offset, 4); // The count is only a hint.
                return self::decodeProperties($data, $offset);
            case self::OBJECT:
                return self::decodeProperties($data, $offset);
            case self::STRICT_ARRAY:
                $count = unpack('N', self::take($data, $offset, 4))[1];
                $values = [];
                for ($i = 0; $i < $count; $i++) {
                    $values[] = self::decodeValue($data, $offset);
                }
                return $values;
            case self::DATE:
                $milliseconds = unpack('E', self::take($data, $offset, 8))[1];
                self::take($data, $offset, 2); // Time zone, unused.
                return $milliseconds;
        }
        throw new UnexpectedValueException("Unsupported AMF0 type $type");
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeProperties(string $data, int &$offset): array
    {
        $properties = [];
        while (true) {
            $key = self::take($data, $offset, unpack('n', self::take($data, $offset, 2))[1]);
            if ($key === '' && \ord($data[$offset] ?? "\0") === self::OBJECT_END) {
                $offset++;
                return $properties;
            }
            $properties[$key] = self::decodeValue($data, $offset);
        }
    }

    private static function take(string $data, int &$offset, int $length): string
    {
        if ($offset + $length > \strlen($data)) {
            throw new UnexpectedValueException('Truncated AMF0 data');
        }
        $value = substr($data, $offset, $length);
        $offset += $length;
        return $value;
    }
}
