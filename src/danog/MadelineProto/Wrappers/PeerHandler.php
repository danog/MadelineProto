<?php
/*
Copyright 2016 Daniil Gentili
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
 * Manages peers.
 */
trait PeerHandler
{
    public function get_info($id, $recursive = true)
    {
        return $this->API->get_info($id, $recursive);
    }

    public function get_pwr_chat($id)
    {
        return $this->API->get_pwr_chat($id);
    }

    public function peer_isset($id)
    {
        return $this->API->peer_isset($id);
    }

    public function gen_all($constructor)
    {
        return $this->API->gen_all($constructor);
    }

    public function resolve_username($username)
    {
        return $this->API->resolve_username($username);
    }
}
