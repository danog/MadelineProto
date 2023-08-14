<?php

declare(strict_types=1);

/**
 * TL callback module.
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

use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;

/**
 * @psalm-type TBeforeMethodResponseDeserialization=Closure(string): void
 * @psalm-type TAfterMethodResponseDeserialization=Closure(MTProtoOutgoingMessage, array): void
 *
 * @psalm-type TBeforeConstructorSerialization=Closure(array): mixed
 * @psalm-type TBeforeConstructorDeserialization=Closure(string): void
 * @psalm-type TAfterConstructorDeserialization=Closure(array): void
 * @psalm-type TTypeMismatch=Closure(array): mixed
 *
 * @internal Interface for managing TL serialization callbacks.
 */
interface TLCallback
{
    /**
     * Called right before deserialization of the result of a method starts.
     *
     * Pass only the method name, will return void
     *
     * @return array<string, list<TBeforeMethodResponseDeserialization>>
     */
    public function getMethodBeforeResponseDeserializationCallbacks(): array;
    /**
     * Called after deserialization of the result of a method.
     *
     * Pass the method name and response, will return void
     *
     * @return array<string, list<TAfterMethodResponseDeserialization>>
     */
    public function getMethodAfterResponseDeserializationCallbacks(): array;
    /**
     * Called right before serialization of constructor.
     *
     * Passed the constructor, will return a modified version.
     *
     * @return array<string, TBeforeConstructorSerialization>
     */
    public function getConstructorBeforeSerializationCallbacks(): array;
    /**
     * Called right before deserialization of constructor.
     *
     * Pass only the constructor name, will return void
     *
     * @return array<string, list<TBeforeConstructorDeserialization>>
     */
    public function getConstructorBeforeDeserializationCallbacks(): array;
    /**
     * Called right after deserialization of constructor.
     *
     * Pass the deserialized constructor, will return void
     *
     * @return array<string, list<TAfterConstructorDeserialization>>
     */
    public function getConstructorAfterDeserializationCallbacks(): array;
    /**
     * Called if constructors of the specified type cannot be serialized.
     *
     * Passed the unserializable constructor,
     * will try to convert it to an constructor of the proper type.
     *
     * @return array<string, TTypeMismatch>
     */
    public function getTypeMismatchCallbacks(): array;
}
