<?php

declare(strict_types=1);

/**
 * UpdatesState class.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

/**
 * Stores the state of updates.
 *
 * @internal
 */
final class UpdatesState
{
    /**
     * PTS.
     */
    private int $pts = 1;
    /**
     * QTS.
     */
    private int $qts = -1;
    /**
     * Seq.
     */
    private int $seq = 0;
    /**
     * Date.
     */
    private int $date = 1;
    /**
     * Init function.
     *
     * @param array $init      Initial parameters
     * @param int   $channelId Channel ID
     */
    public function __construct(array $init = [], public readonly int $channelId = 0)
    {
        $this->update($init);
    }
    /**
     * Sleep function.
     *
     * @return array Parameters to serialize
     */
    public function __sleep(): array
    {
        return $this->channelId ? ['pts', 'channelId'] : ['pts', 'qts', 'seq', 'date', 'channelId'];
    }
    /**
     * Is this state relative to a channel?
     */
    public function isChannel(): bool
    {
        return (bool) $this->channelId;
    }
    /**
     * Update multiple parameters.
     *
     * @param array $init Parameters to update
     */
    public function update(array $init): self
    {
        foreach ($this->channelId ? ['pts'] : ['pts', 'qts', 'seq', 'date'] as $param) {
            if (isset($init[$param])) {
                $this->{$param}($init[$param]);
            }
        }
        return $this;
    }
    /**
     * Get/set PTS.
     *
     * @param  int $set PTS to set
     * @return int PTS
     */
    public function pts(int $set = 0): int
    {
        if ($set !== 0 && $set > $this->pts) {
            $this->pts = $set;
        }
        return $this->pts;
    }
    /**
     * Get/set QTS.
     *
     * @param  int $set QTS to set
     * @return int QTS
     */
    public function qts(int $set = 0): int
    {
        if ($set !== 0 && $set > $this->qts) {
            $this->qts = $set;
        }
        return $this->qts;
    }
    /**
     * Get/set seq.
     *
     * @param  int $set Seq to set
     * @return int seq
     */
    public function seq(int $set = 0): int
    {
        if ($set !== 0 && $set > $this->seq) {
            $this->seq = $set;
        }
        return $this->seq;
    }
    /**
     * Get/set date.
     *
     * @param  int $set Date to set
     * @return int Date
     */
    public function date(int $set = 0): int
    {
        if ($set !== 0 && $set > $this->date) {
            $this->date = $set;
        }
        return $this->date;
    }
    /**
     * Check validity of PTS contained in update.
     *
     * @param  array $update Update
     * @return int   -1 if it's too old, 0 if it's ok, 1 if it's too new
     */
    public function checkPts(array $update): int
    {
        return $update['pts'] - ($this->pts + $update['pts_count']);
    }
    /**
     * Check validity of seq contained in update.
     *
     * @param  int $seq Seq
     * @return int -1 if it's too old, 0 if it's ok, 1 if it's too new
     */
    public function checkSeq(int $seq): int
    {
        return $seq ? $seq - ($this->seq + 1) : $seq;
    }
}
