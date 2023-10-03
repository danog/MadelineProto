<?php

declare(strict_types=1);

/**
 * Bytes module.
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

namespace danog\MadelineProto\TL\Types;

use ArrayAccess;
use AssertionError;
use JsonSerializable;

/**
 * Bytes wrapper.
 *
 * Cast this object to a string ((string) $bytes) to obtain the inner bytes.
 *
 * @implements ArrayAccess<int, string>
 */
final class Bytes implements JsonSerializable, ArrayAccess
{
    /**
     * Constructor function.
     *
     * @param string $bytes Contents
     */
    public function __construct(private readonly string $bytes)
    {
    }
    /**
     * Sleep function.
     */
    public function __sleep(): array
    {
        return ['bytes'];
    }
    /**
     * Cast bytes to string.
     */
    public function __toString(): string
    {
        return $this->bytes;
    }
    /**
     * Obtain values for JSON-encoding.
     */
    public function jsonSerialize(): array
    {
        return ['_' => 'bytes', 'bytes' => base64_encode($this->bytes)];
    }
    /**
     * Set char at offset.
     *
     * @param integer|null $offset Offset
     * @param string       $value  Char
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new AssertionError("Cannot modify nested bytes!");
    }
    /**
     * Get char at offset.
     *
     * @param  integer $offset Name
     * @return string
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->bytes[$offset];
    }
    /**
     * Unset char at offset.
     *
     * @param integer $offset Offset
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new AssertionError("Cannot modify nested bytes!");
    }
    /**
     * Check if char at offset exists.
     *
     * @param integer $offset Offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->bytes[$offset]);
    }
}
