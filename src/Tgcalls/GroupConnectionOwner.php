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

namespace danog\MadelineProto\Tgcalls;

use Stringable;

/**
 * The call a {@see GroupConnection} belongs to: the small surface the WebRTC engine calls back into,
 * implemented by both the ordinary group call ({@see \danog\MadelineProto\GroupCallController}) and
 * the end-to-end encrypted conference call, so the same engine drives either.
 *
 * @internal
 */
interface GroupConnectionOwner extends Stringable
{
    public function log(string $message, int $level = \danog\MadelineProto\Logger::NOTICE): void;

    /** A participant's media source started arriving. */
    public function onIncomingSource(int $source): void;

    /** The WebRTC connection failed and should be checked/rejoined. */
    public function onConnectionFailed(): void;

    /** Tell the server whether our camera video is stopped. */
    public function setVideoStopped(bool $stopped): void;

    /** Tell the server whether our screen-share is paused. */
    public function setPresentationPaused(bool $paused): void;
}
