<?php

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\Wrappers;

/**
 * Manages logging in and out.
 */
trait Loop
{
    public function loop()
    {
        if (in_array($this->settings['updates']['callback'], [['danog\\MadelineProto\\API', 'get_updates_update_handler'], 'get_updates_update_handler'])) {
            return true;
        }
        $offset = 0;
        while (true) {
            $updates = $this->get_updates(['offset' => $offset]);
            foreach ($updates as $update) {
                $offset = $update['update_id'] + 1;
                $this->settings['updates']['callback']($update);
            }
        }
    }
}
