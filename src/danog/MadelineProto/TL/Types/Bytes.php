<?php

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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\TL\Types;

/**
 * Bytes wrapper.
 */
class Bytes implements \JsonSerializable, \ArrayAccess
{
    /**
     * Bytes.
     *
     * @var string Bytes
     */
    private string $bytes;
    /**
     * Constructor function.
     *
     * @param string $bytes Contents
     */
    public function __construct(string $bytes)
    {
        $this->bytes = $bytes;
    }
    /**
     * Sleep function.
     *
     * @return array
     */
    public function __sleep(): array
    {
        return ['bytes'];
    }
    /**
     * Cast bytes to string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->bytes;
    }
    /**
     * Obtain values for JSON-encoding.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return ['_' => 'bytes', 'bytes' => \base64_encode($this->bytes)];
    }
    /**
     * Set char at offset.
     *
     * @param integer|null $offset Offset
     * @param string       $value  Char
     *
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->bytes .= $value;
        } else {
            $this->bytes[$offset] = $value;
        }
    }
    /**
     * Get char at offset.
     *
     * @param integer $offset Name
     *
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
     *
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->bytes[$offset]);
    }
    /**
     * Check if char at offset exists.
     *
     * @param integer $offset Offset
     *
     * @return boolean
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->bytes[$offset]);
    }
}
