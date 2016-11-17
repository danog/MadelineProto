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

namespace danog\MadelineProto;

/**
 * Manages datacenters.
 */
class DataCenter extends Tools
{
    public $referenced_variables = ["time_delta", "temp_auth_key", "auth_key", "session_id", "seq_no"];
    public $sockets;

    public function __construct($dclist, $settings)
    {
        $this->dclist = $dclist;
        $this->settings = $settings;
        if (isset($this->settings['all'])) {
            foreach ($this->range(1, 6) as $n) {
                $this->settings[$n] = $this->settings['all'];
            }
            unset($this->settings['all']);
        }
        foreach ($this->range(1, 6) as $n) {
            if (!isset($this->settings[$n])) {
                $this->settings[$n] = [
                    'protocol'  => 'tcp_full',
                    'port'      => '443',
                    'test_mode' => true,
                ];
            }
        }
    }

    public function dc_disconnect($dc_number)
    {
        if ($this->curdc == $dc_number) {
            $this->unset_curdc();
        }
        if (isset($this->sockets[$dc_number])) {
            \danog\MadelineProto\Logger::log('Disconnecting from DC '.$dc_number.'...');
            unset($this->sockets[$dc_number]);
        }
    }

    public function dc_connect($dc_number, $settings = [])
    {
        if (isset($this->sockets[$dc_number])) {
            return false;
            $this->set_curdc($dc_number);
        }
        $this->set_curdc($dc_number);

        \danog\MadelineProto\Logger::log('Connecting to DC '.$dc_number.'...');

        if ($settings == []) {
            $settings = $this->settings[$dc_number];
        }
        $address = $settings['test_mode'] ? $this->dclist['test'][$dc_number] : $this->dclist['main'][$dc_number];
        if ($settings['protocol'] == 'https') {
            $subdomain = $this->dclist['ssl_subdomains'][$dc_number].($settings['upload'] ? '-1' : '');
            $path = $settings['test_mode'] ? 'apiw_test1' : 'apiw1';
            $address = 'https://'.$subdomain.'.web.telegram.org/'.$path;
        }
        $this->sockets[$dc_number] = new Connection($address, $settings['port'], $settings['protocol']);
        return true;
    }

    public function set_curdc($dc_number) {
        $this->curdc = $dc_number;
        foreach ($this->referenced_variables as $key) {
            $this->{$key} = &$this->sockets[$dc_number]->{$key};            
        }
    }
    public function unset_curdc($dc_number) {
        unset($this->curdc);
        foreach ($this->referenced_variables as $key) {
            unset($this->sockets[$dc_number]->{$key});
        }
    }


    public function __call($name, $arguments)
    {
        return $this->sockets[$this->curdc]->{$name}(...$arguments);
    }

    public function __destroy()
    {
        $this->unset_curdc();
        foreach ($this->sockets as $n => $socket) {
            unset($this->sockets[$n]);
        }
    }
}
