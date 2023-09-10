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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\TL;

interface TLInterface
{
    /**
     * Get constructors.
     */
    public function getConstructors(): TLConstructors;
    /**
     * Get methods.
     */
    public function getMethods(): TLMethods;
    /**
     * Get descriptions.
     *
     */
    public function getDescriptions(): array;
    /**
     * Get TL namespaces.
     */
    public function getMethodNamespaces(): array;
    /**
     * Get namespaced methods (method => namespace).
     */
    public function getMethodsNamespaced(): array;
    /**
     * Serialize TL object.
     *
     * @param array   $type   TL type definition
     * @param mixed   $object Object to serialize
     * @param string  $ctx    Context
     * @param integer $layer  Layer version
     */
    public function serializeObject(array $type, mixed $object, string|int $ctx, int $layer = -1);
    /**
     * Serialize method.
     *
     * @param string $method    Method name
     * @param mixed  $arguments Arguments
     */
    public function serializeMethod(string $method, mixed $arguments);
    /**
     * Get length of TL payload.
     *
     * @param resource|string $stream Stream
     * @param array           $type   Type identifier
     */
    public function getLength($stream, array $type = ['type' => '']): int;
    /**
     * Deserialize TL object.
     *
     * @param string|resource $stream Stream
     * @param array           $type   Type identifier
     */
    public function deserialize($stream, array $type);

    /**
     * Get secret chat layer version.
     */
    public function getSecretLayer(): int;
}
