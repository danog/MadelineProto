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

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\SettingsAbstract;

/**
 * VoIP settings.
 *
 * @psalm-external-mutation-free
 */
final class VoIP extends SettingsAbstract
{
    /**
     * How far ahead of playback the files played in calls are read, in milliseconds.
     */
    protected int $playbackLookahead = 0;

    /**
     * Get how far ahead of playback the files played in calls are read, in milliseconds.
     */
    public function getPlaybackLookahead(): int
    {
        return $this->playbackLookahead;
    }

    /**
     * Set how far ahead of playback the files played in calls are read, in milliseconds.
     *
     * The frames read ahead are kept in memory, for every call: by default (0), only the next frame is
     * read ahead, to keep memory usage to a minimum. Reading further ahead avoids stuttering when the
     * files are read from a slow source (like a slow URL), at the cost of about the file's bitrate
     * times the lookahead of memory per call.
     *
     * @param int<0, max> $playbackLookahead Lookahead, in milliseconds.
     */
    public function setPlaybackLookahead(int $playbackLookahead): self
    {
        if ($playbackLookahead < 0) {
            throw new \AssertionError("The playback lookahead can't be negative!");
        }
        $this->playbackLookahead = $playbackLookahead;

        return $this;
    }
}
