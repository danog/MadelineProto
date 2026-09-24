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

use danog\MadelineProto\LocalFile;
use danog\MadelineProto\RecordingEvent;

/**
 * Told by an {@see IncomingMedia} router when the streams a participant sends (or their codecs)
 * change, or a recording of them starts or ends.
 *
 * @internal
 */
interface IncomingMediaObserver
{
    /**
     * @param ?RecordingEvent $recording Whether a recording started or ended, or null if only the streams changed.
     * @param ?LocalFile      $file      The file of the recording event, if it is a local file.
     *
     * @psalm-impure
     */
    public function onIncomingMediaChanged(IncomingMedia $media, ?RecordingEvent $recording, ?LocalFile $file): void;
}
