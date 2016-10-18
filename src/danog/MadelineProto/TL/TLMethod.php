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

class TLMethod
{
    public function __construct($json_dict)
    {
        $this->id = (int) $json_dict['id'];
        $this->type = $json_dict['type'];
        $this->method = $json_dict['method'];
        $this->params = $json_dict['params'];
        foreach ($this->params as &$param) {
            $param['opt'] = false;
            $param['subtype'] = '';
            if (preg_match('/^flags\.\d\?/',  $param['type'])) {
                $param['opt'] = true;
                $param['flag'] = preg_replace(['/^flags\./', '/\?.*/'], '', $param['type']);
                $param['type'] = preg_replace('/^flags\.\d\?/', '', $param['type']);
            }
            if (preg_match('/vector<.*>/i',  $param['type'])) {
                if (preg_match('/vector/', $param['type'])) {
                    $param['subtype'] = preg_replace(['/.*</', '/>$/'], '', $param['type']);
                    $param['type'] = 'vector';
                }
                if (preg_match('/Vector/', $param['type'])) {
                    $param['subtype'] = preg_replace(['/.*</', '/>$/'], '', $param['type']);
                    $param['type'] = 'Vector t';
                }
                if (preg_match('/^\%/', $param['subtype'])) {
                    $param['subtype'] = lcfirst(preg_replace('/^\%/', '', $param['subtype']));
                }
            }
        }
    }
}
