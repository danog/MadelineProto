<?php
/*
Copyright 2016 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with the MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\TL;

class TLConstructor
{
    public function __construct($json_dict)
    {
        $this->id = (int) $json_dict['id'];
        $this->type = $json_dict['type'];
        $this->predicate = $json_dict['predicate'];
        $this->params = [];
        foreach ($json_dict['params'] as $param) {
            if (($param['type'] == 'Vector<long>')) {
                $param['type'] = 'Vector t';
                $param['subtype'] = 'long';
            } elseif (($param['type'] == 'vector<%Message>')) {
                $param['type'] = 'vector';
                $param['subtype'] = 'message';
            } elseif (($param['type'] == 'vector<future_salt>')) {
                $param['type'] = 'vector';
                $param['subtype'] = 'future_salt';
            } else {
                $param['subtype'] = null;
            }
            $this->params[] = $param;
        }
    }
}
