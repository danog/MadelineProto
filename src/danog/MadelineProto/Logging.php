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
/*
 * Logging class
 */

namespace danog\MadelineProto;

class Logging
{
    public $mode = null;
    public $optional = null;

    /*
     * Constructor function
     * Accepts various logging modes:
     * 0 - No logging
     * 1 - Log to the default logging destination
     * 2 - Log to file defined in second parameter
     * 3 - Echo logs
     */
    public function __construct($mode, $optional = null)
    {
        $this->mode = (string) $mode;
        $this->optional = $optional;
    }

    public function __invoke(...$params)
    {
        foreach ($params as $param) {
            switch ($this->mode) {
                case '1':
                    error_log($param);
                    break;
                case '2':
                    error_log($param, 3, $this->optional);
                    break;
                case '3':
                    echo $param.PHP_EOL;
                    break;
                default:
                    break;
            }
        }
    }

    public function log(...$params)
    {
        if ($this->mode == null) {
            $mode = array_pop($params);
        } else {
            $mode = $this->mode;
        }
        foreach ($params as $param) {
        if (!is_string($param)) {
            $param = var_export($param, true);
        }
            switch ($mode) {
                case '1':
                    error_log($param);
                    break;
                case '2':
                    error_log($param, 3, $this->optional);
                    break;
                case '3':
                    echo $param.PHP_EOL;
                    break;
                default:
                    break;
            }
        }
    }
}
