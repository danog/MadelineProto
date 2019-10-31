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
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

/**
 * Stores multiple update states.
 */
class CombinedUpdatesState
{
    /**
     * Update states.
     *
     * @var array<UpdatesState>
     */
    private $states = [];

    /**
     * Constructor function.
     *
     * @param array $init Initial array of states
     */
    public function __construct($init = [])
    {
        $this->states[false] = new UpdatesState();
        if (!\is_array($init)) {
            return;
        }
        foreach ($init as $channel => $state) {
            if (\is_array($state)) {
                $state = new UpdatesState($state, $channel);
            }
            $this->states[$channel] = $state;
        }
    }

    /**
     * Get or update multiple parameters.
     *
     * @param int   $channel Channel to get info about (optional, if not provided returns the entire info array)
     * @param array $init    Parameters to update
     *
     * @return UpdatesState|UpdatesState[]
     */
    public function get(int $channel = null, array $init = [])
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
     * @param int $channel Channel whose state should be removed
     *
     * @return void
     */
    public function remove(int $channel)
    {
        if (isset($this->states[$channel])) {
            unset($this->states[$channel]);
        }
    }

    /**
     * Check if update state is present.
     *
     * @param int $channel Channel ID
     *
     * @return bool
     */
    public function has(int $channel): bool
    {
        return isset($this->states[$channel]);
    }

    /**
     * Are we currently busy?
     *
     * @param int       $channel Channel to get info about
     * @param bool|null $set     Busy flag to set before returning
     *
     * @return bool
     */
    public function syncLoading(int $channel, bool $set = null): bool
    {
        return $this->get($channel)->syncLoading($set);
    }
}
