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

use Amp\ByteStream\WritableStream;
use danog\MadelineProto\LocalFile;

/**
 * Told by a {@see CallRecorder} when it opens or finishes an output file (or stream).
 *
 * @internal
 */
interface RecordingObserver
{
    /**
     * The recorder wrote the header of a file (or stream): media is recorded into it from now on.
     *
     * @psalm-impure
     */
    public function onRecordingStarted(CallRecorder $recorder, LocalFile|WritableStream $out): void;

    /**
     * The recorder finished a file (or stream): nothing more is written to it.
     *
     * @psalm-impure
     */
    public function onRecordingEnded(CallRecorder $recorder, LocalFile|WritableStream $out): void;
}
