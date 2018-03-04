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

namespace danog\MadelineProto\TL;

trait TLParams
{
    public function parse_params($key, $mtproto = false)
    {
        foreach ($this->by_id[$key]['params'] as $kkey => $param) {
            if (preg_match('/^flags\.(\d*)\?(.*)/', $param['type'], $matches)) {
                $param['pow'] = pow(2, $matches[1]);
                $param['type'] = $matches[2];
            }
            if (preg_match('/^(v|V)ector\<(.*)\>$/', $param['type'], $matches)) {
                $param['type'] = $matches[1] === 'v' ? 'vector' : 'Vector t';
                $param['subtype'] = $matches[2];
                $param['subtype'] = ($mtproto && $param['subtype'] === 'Message' ? 'MT' : '').$param['subtype'];
                $param['subtype'] = $mtproto && $param['subtype'] === '%Message' ? '%MTMessage' : $param['subtype'];
            }
            $param['type'] = ($mtproto && $param['type'] === 'Message' ? 'MT' : '').$param['type'];
            $param['type'] = $mtproto && $param['type'] === '%Message' ? '%MTMessage' : $param['type'];
            $this->by_id[$key]['params'][$kkey] = $param;
        }
    }
}
