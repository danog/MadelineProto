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

use InvalidArgumentException;

/**
 * The media streams a participant of a call can send, as bit flags.
 *
 * Every party of a call has up to three streams: their microphone ({@see self::AUDIO}), their
 * camera ({@see self::VIDEO}) and their screen share ({@see self::SCREEN}), each of which they can
 * turn on and off at any time. A *set* of streams is a bitmask of these flags, as used by
 * {@see EventHandler\Call::setOutput()} to pick the tracks of a recording and reported by
 * {@see EventHandler\Calls\CallStreams} updates.
 *
 * @psalm-immutable
 */
final class CallStream
{
    /** The microphone audio (OPUS). */
    public const AUDIO = 1;
    /** The camera video. */
    public const VIDEO = 2;
    /** The screen share (presentation) video. */
    public const SCREEN = 4;
    /** Every stream. */
    public const ALL = self::AUDIO | self::VIDEO | self::SCREEN;

    /** The name of each flag, in the order they are listed. */
    public const NAMES = [self::AUDIO => 'audio', self::VIDEO => 'video', self::SCREEN => 'screen'];

    /**
     * @psalm-mutation-free
     */
    private function __construct()
    {
    }

    /**
     * The names of the streams in a set, in order: `audio`, `video`, `screen`.
     *
     * @return list<string>
     *
     * @psalm-pure
     */
    public static function names(int $streams): array
    {
        $names = [];
        foreach (self::NAMES as $flag => $name) {
            if ($streams & $flag) {
                $names[] = $name;
            }
        }
        return $names;
    }

    /**
     * A human-readable description of a set of streams: `audio+video`, or `nothing` for an empty set.
     *
     * @psalm-pure
     */
    public static function describe(int $streams): string
    {
        return $streams === 0 ? 'nothing' : implode('+', self::names($streams));
    }

    /**
     * Build a set from the state of each stream.
     *
     * @psalm-pure
     */
    public static function of(bool $audio, bool $video, bool $screen): int
    {
        return ($audio ? self::AUDIO : 0) | ($video ? self::VIDEO : 0) | ($screen ? self::SCREEN : 0);
    }

    /**
     * Check that every stream of a chosen set is available (and that, with no choice, something is).
     *
     * @param ?int   $streams   The chosen set, or null for "everything available".
     * @param int    $available The streams currently available.
     * @param string $who       Who sends them, for the error message.
     *
     * @throws InvalidArgumentException
     *
     * @internal
     *
     * @psalm-pure
     */
    public static function checkAvailable(?int $streams, int $available, string $who): void
    {
        if ($streams === null) {
            if ($available === 0) {
                throw new InvalidArgumentException("$who is not sending anything to record right now: wait for a CallStreams update announcing a stream.");
            }
            return;
        }
        $missing = $streams & ~$available;
        if ($missing !== 0) {
            throw new InvalidArgumentException("$who is not sending ".self::describe($missing).' right now (only '.self::describe($available).'): wait for a CallStreams update announcing it, or record only what is available.');
        }
    }

    /**
     * Check that a set only holds known flags.
     *
     * @throws InvalidArgumentException
     *
     * @psalm-pure
     */
    public static function validate(int $streams): void
    {
        if ($streams & ~self::ALL) {
            throw new InvalidArgumentException("Unknown stream flags in $streams: use a bitmask of CallStream::AUDIO, CallStream::VIDEO and CallStream::SCREEN.");
        }
    }
}
