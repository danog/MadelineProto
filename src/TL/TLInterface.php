<?php

declare(strict_types=1);

/**
 * TL module.
 *
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

namespace danog\MadelineProto\TL;

/** @psalm-mutable */
interface TLInterface
{
    /**
     * Get constructors.
     * @psalm-impure
     */
    public function getConstructors(): TLConstructors;
    /**
     * Get methods.
     * @psalm-impure
     */
    public function getMethods(): TLMethods;
    /**
     * Get descriptions.
     *
     * @psalm-impure
     */
    public function getDescriptions(): array;
    /**
     * Get TL namespaces.
     * @psalm-impure
     */
    public function getMethodNamespaces(): array;
    /**
     * Get namespaced methods (method => namespace).
     * @psalm-impure
     */
    public function getMethodsNamespaced(): array;
    /**
     * Serialize TL object.
     *
     * @param array   $type   TL type definition
     * @param mixed   $object Object to serialize
     * @param string  $ctx    Context
     * @param integer $layer  Layer version
     * @psalm-impure
     */
    public function serializeObject(array $type, mixed $object, string|int $ctx, int $layer = -1);
    /**
     * Serialize method.
     *
     * @param string $method    Method name
     * @param mixed  $arguments Arguments
     * @psalm-impure
     */
    public function serializeMethod(string $method, mixed $arguments);
    /**
     * Get length of TL payload.
     *
     * @param resource|string $stream Stream
     * @param array           $type   Type identifier
     * @psalm-impure
     */
    public function getLength($stream, array $type = ['type' => '', 'connection' => null, 'encrypted' => false]): int;
    /**
     * Deserialize TL object.
     *
     * @param string|resource $stream Stream
     * @param array           $type   Type identifier
     * @psalm-impure
     */
    public function deserialize($stream, array $type);

    /**
     * Get secret chat layer version.
     * @psalm-impure
     */
    public function getSecretLayer(): int;
}
