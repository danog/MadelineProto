<?php
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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\TL;

/**
 * Interface for managing TL serialization callbacks.
 */
interface TLCallback
{
    /**
     *
     *
     * @var int
     */
    const METHOD_CALLBACK = 0;
    const METHOD_BEFORE_CALLBACK = 1;
    const CONSTRUCTOR_CALLBACK = 2;
    const CONSTRUCTOR_BEFORE_CALLBACK = 3;
    const CONSTRUCTOR_SERIALIZE_CALLBACK = 4;
    /**
     * Called if objects of the specified type cannot be serialized.
     *
     * @var int
     */
    const TYPE_MISMATCH_CALLBACK = 5;

    /**
     * Called after serialization of method.
     *
     * Pass the method name and arguments
     *
     * @return array
     */
    public function getMethodCallbacks(): array;

    /**
     * Called right before serialization of method starts.
     *
     * Pass the method name
     *
     * @return array
     */
    public function getMethodBeforeCallbacks(): array;

    /**
     * Called right after deserialization of object, passing the final object.
     *
     * @return array
     */
    public function getConstructorCallbacks(): array;

    /**
     * Called right before deserialization of object.
     *
     * Pass only the constructor name
     *
     * @return array
     */
    public function getConstructorBeforeCallbacks(): array;

    /**
     * Called right before serialization of constructor.
     *
     * Passed the object, will return a modified version.
     *
     * @return array
     */
    public function getConstructorSerializeCallbacks(): array;

    /**
     * Called if objects of the specified type cannot be serialized.
     *
     * Passed the unserializable object,
     * will try to convert it to an object of the proper type.
     *
     * @return array
     */
    public function getTypeMismatchCallbacks(): array;
}
