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
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\TL;

interface TLCallback
{
    const METHOD_CALLBACK = 0;
    const METHOD_BEFORE_CALLBACK = 1;
    const CONSTRUCTOR_CALLBACK = 2;
    const CONSTRUCTOR_BEFORE_CALLBACK = 3;
    const CONSTRUCTOR_SERIALIZE_CALLBACK = 4;
    const TYPE_MISMATCH_CALLBACK = 5;

    public function getMethodCallbacks(): array;

    public function getMethodBeforeCallbacks(): array;

    public function getConstructorCallbacks(): array;

    public function getConstructorBeforeCallbacks(): array;

    public function getConstructorSerializeCallbacks(): array;

    public function getTypeMismatchCallbacks(): array;
}
