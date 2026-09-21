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

/**
 * Which media stream of a call a playback or recording operation targets.
 *
 * A call carries two independent outgoing video streams: the main camera stream (transmitted
 * alongside the microphone audio) and a separate presentation (screen-sharing) stream. The playlist
 * controls ({@see Call::play()} and friends) take one of these to say which stream they act on; it
 * defaults to {@see self::Camera}. Recordings ({@see Call::setOutput()}) always hold both.
 */
enum MediaDestination
{
    /** The main stream: the camera video, transmitted together with the microphone audio. */
    case Camera;
    /** The presentation stream: a separate screen-sharing (screencast) video. */
    case Presentation;
}
