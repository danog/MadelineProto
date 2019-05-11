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
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

/**
 * Stores the state of updates
 */
class UpdatesState
{
    /**
     * PTS
     *
     * @var int
     */
    private $pts = 1;
    /**
     * QTS
     *
     * @var int
     */
    private $qts = 0;
    /**
     * Seq
     *
     * @var int
     */
    private $seq = 0;
    /**
     * Date
     * 
     * @var int
     */
    private $date = 1;

    /**
     * Channel ID
     *
     * @var int|bool
     */
    private $channelId;

    /**
     * Is busy?
     *
     * @var boolean
     */
    private $syncLoading = false;

    /**
     * Init function 
     *
     * @param array $init Initial parameters
     * @param boolean $channelId Channel ID
     */
    public function __construct($init = [], $channelId = false)
    {
        $this->channelId = $channelId;
        $this->update($init);
    }
    /**
     * Sleep function
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
     * @return boolean
     */
    public function isChannel()
    {
        return (bool) $this->channelId;
    }
    /**
     * Get the channel ID
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
     * @param boolean|null $set
     * @return boolean
     */
    public function syncLoading($set = null)
    {
        if ($set !== null) {
            $this->syncLoading = $set;
        }
        return $this->syncLoading;
    }

    /**
     * Update multiple parameters
     *
     * @param array $init
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
     * Get/set PTS
     *
     * @param integer $set
     * @return integer
     */
    public function pts($set = 0)
    {
        if ($set !== 0) {
            $this->pts = $set;
        }
        return $this->pts;
    }
    /**
     * Get/set QTS
     *
     * @param integer $set
     * @return integer
     */
    public function qts($set = 0)
    {
        if ($set !== 0) {
            $this->qts = $set;
        }
        return $this->qts;
    }
    /**
     * Get/set seq
     *
     * @param integer $set
     * @return integer
     */
    public function seq($set = 0)
    {
        if ($set !== 0) {
            $this->seq = $set;
        }
        return $this->seq;
    }
    /**
     * Get/set date
     *
     * @param integer $set
     * @return integer
     */
    public function date($set = 0)
    {
        if ($set !== 0 && $set > $this->date) {
            $this->date = $set;
        }
        return $this->date;
    }
}
