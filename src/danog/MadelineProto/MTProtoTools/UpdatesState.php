<?php

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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

/**
 * Stores the state of updates.
 */
class UpdatesState
{
    /**
     * PTS.
     *
     * @var int
     */
    private $pts = 1;
    /**
     * QTS.
     *
     * @var int
     */
    private $qts = -1;
    /**
     * Seq.
     *
     * @var int
     */
    private $seq = 0;
    /**
     * Date.
     *
     * @var int
     */
    private $date = 1;

    /**
     * Channel ID.
     *
     * @var int|bool
     */
    private $channelId;

    /**
     * Is busy?
     *
     * @var bool
     */
    private $syncLoading = false;

    /**
     * Init function.
     *
     * @param array $init      Initial parameters
     * @param bool  $channelId Channel ID
     */
    public function __construct($init = [], $channelId = false)
    {
        $this->channelId = $channelId;
        $this->update($init);
    }

    /**
     * Sleep function.
     *
     * @return array Parameters to serialize
     */
    public function __sleep()
    {
        return $this->channelId ? ['pts', 'channelId'] : ['pts', 'qts', 'seq', 'date', 'channelId'];
    }

    /**
     * Is this state relative to a channel?
     *
     * @return bool
     */
    public function isChannel()
    {
        return (bool) $this->channelId;
    }

    /**
     * Get the channel ID.
     *
     * @return int|null
     */
    public function getChannel()
    {
        return $this->channelId;
    }

    /**
     * Are we currently busy?
     *
     * @param bool|null $set
     *
     * @return bool
     */
    public function syncLoading($set = null)
    {
        if ($set !== null) {
            $this->syncLoading = $set;
        }

        return $this->syncLoading;
    }

    /**
     * Update multiple parameters.
     *
     * @param array $init
     *
     * @return self
     */
    public function update($init)
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
     * @param int $set
     *
     * @return int
     */
    public function pts($set = 0)
    {
        if ($set !== 0 && $set > $this->pts) {
            $this->pts = $set;
        }

        return $this->pts;
    }

    /**
     * Get/set QTS.
     *
     * @param int $set
     *
     * @return int
     */
    public function qts($set = 0)
    {
        if ($set !== 0 && $set > $this->qts) {
            $this->qts = $set;
        }

        return $this->qts;
    }

    /**
     * Get/set seq.
     *
     * @param int $set
     *
     * @return int
     */
    public function seq($set = 0)
    {
        if ($set !== 0 && $set > $this->seq) {
            $this->seq = $set;
        }

        return $this->seq;
    }

    /**
     * Get/set date.
     *
     * @param int $set
     *
     * @return int
     */
    public function date($set = 0)
    {
        if ($set !== 0 && $set > $this->date) {
            $this->date = $set;
        }

        return $this->date;
    }

    /**
     * Check validity of PTS contained in update.
     *
     * @param array $update
     *
     * @return int -1 if it's too old, 0 if it's ok, 1 if it's too new
     */
    public function checkPts($update)
    {
        return $update['pts'] - ($this->pts + $update['pts_count']);
    }

    /**
     * Check validity of seq contained in update.
     *
     * @param int $seq
     *
     * @return int -1 if it's too old, 0 if it's ok, 1 if it's too new
     */
    public function checkSeq($seq)
    {
        return $seq ? $seq - ($this->seq + 1) : $seq;
    }
}
