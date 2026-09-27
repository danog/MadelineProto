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

namespace danog\MadelineProto;

use danog\MadelineProto\EventHandler\Calls\CallDenialReason;

/**
 * Thrown when a call or group call cannot be started in a chat, because of our own permissions,
 * the other party's privacy settings, or the kind of chat/account involved.
 *
 * Use `canRequestCall()` on a message to check in advance whether a call can be started.
 */
final class CallNotAllowedException extends Exception
{
    /** @internal */
    public function __construct(
        /** Why the call cannot be started. */
        public readonly CallDenialReason $reason
    ) {
        parent::__construct($reason->value);
    }
}
