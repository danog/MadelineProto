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

namespace danog\MadelineProto;

/**
 * Manages datacenters.
 */
class DataCenter extends Tools
{
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
        $this->dc_connect(2);
    }

    public function dc_disconnect($dc_number) {
        unset($this->sockets[$dc_number]);
    }

    public function dc_connect($dc_number, $settings = [])
    {
        if (isset($this->sockets[$dc_number])) {
            return;
        }
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
        $this->curdc = $dc_number;
    }

    public function send_message($message, $dc_number = -1)
    {
        if ($dc_number == -1) {
            $dc_number = $this->curdc;
        }

        return $this->sockets[$dc_number]->send_message($message);
    }

    public function read_message($dc_number = -1)
    {
        if ($dc_number == -1) {
            $dc_number = $this->curdc;
        }

        return $this->sockets[$dc_number]->read_message();
    }

    public function __destroy()
    {
        foreach ($this->sockets as $n => $socket) {
            unset($this->sockets[$n]);
        }
    }
}
