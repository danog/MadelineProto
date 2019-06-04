<?php

/**
 * CombinedUpdatesState class.
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
 * Stores multiple states.
 */
class CombinedUpdatesState
{
    private $states = [];

    public function __construct($init = [])
    {
        $this->states[false] = new UpdatesState();
        if (!is_array($init)) {
            return;
        }
        foreach ($init as $channel => $state) {
            if (is_array($state)) {
                $state = new UpdatesState($state, $channel);
            }
            $this->states[$channel] = $state;
        }
    }

    /**
     * Update multiple parameters.
     *
     * @param array|null $init
     * @param int        $channel
     *
     * @return UpdatesState
     */
    public function get($channel = null, $init = [])
    {
        if ($channel === null) {
            return $this->states;
        }
        if (!isset($this->states[$channel])) {
            return $this->states[$channel] = new UpdatesState($init, $channel);
        }

        return $this->states[$channel]->update($init);
    }

    /**
     * Remove update state.
     *
     * @param int $channel
     *
     * @return void
     */
    public function remove($channel)
    {
        if (isset($this->states[$channel])) {
            unset($this->states[$channel]);
        }
    }

    /**
     * Check if update state is present.
     *
     * @param int $channel
     *
     * @return void
     */
    public function has($channel)
    {
        return isset($this->states[$channel]);
    }

    /**
     * Are we currently busy?
     *
     * @param int       $channel
     * @param bool|null $set
     *
     * @return bool
     */
    public function syncLoading($channel, $set = null)
    {
        return $this->get($channel)->syncLoading($set);
    }
}
