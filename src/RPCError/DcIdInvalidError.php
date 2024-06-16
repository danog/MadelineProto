<?php declare(strict_types=1);
/**
 * DcIdInvalidError error.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2024 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\RPCError;

use danog\MadelineProto\RPCErrorException;

/**
 * The provided DC ID is invalid.
 *
 * Note: this exception is part of the raw API, and thus is not covered by the backwards-compatibility promise.
 *
 * Always check the changelog when upgrading, and use tools like Psalm to easily upgrade your code.
 */
final class DcIdInvalidError extends RPCErrorException
{
    protected function __construct(int $code, string $caller, ?\Exception $previous = null)
    {
        parent::__construct('DC_ID_INVALID', 'The provided DC ID is invalid.', $code, $caller, $previous);
    }
}
