<?php
/*
Copyright 2016-2017 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\TL;

trait PrettyException
{
    public $tl_trace;

    public function getTLTrace()
    {
        return $this->tl_trace;
    }

    public function prettify_tl($init = '')
    {
        $tl = false;
        foreach (array_reverse($this->getTrace()) as $k => $frame) {
            if (isset($frame['function']) && in_array($frame['function'], ['serialize_params', 'serialize_object'])) {
                if ($frame['args'][2] !== '') {
                    $this->tl_trace .= $tl ? "['".$frame['args'][2]."']" : "While serializing:  \t".$frame['args'][2];
                    $tl = true;
                }
            } else {
                if ($tl) {
                    $this->tl_trace .= PHP_EOL;
                }
                if (isset($frame['function']) && ($frame['function'] === 'handle_rpc_error' && $k === count($this->getTrace()) - 1) || $frame['function'] === 'unserialize') {
                    continue;
                }
                $this->tl_trace .= isset($frame['file']) ? str_pad(basename($frame['file']).'('.$frame['line'].'):', 20)."\t" : '';
                $this->tl_trace .= isset($frame['function']) ? $frame['function'].'(' : '';
                $this->tl_trace .= isset($frame['args']) ? substr(json_encode($frame['args']), 1, -1) : '';
                $this->tl_trace .= ')';
                $this->tl_trace .= PHP_EOL;
                $tl = false;
            }
        }
        $this->tl_trace .= $init !== '' ? "['".$init."']" : '';
        $this->tl_trace = implode(PHP_EOL, array_reverse(explode(PHP_EOL, $this->tl_trace)));
    }
}
