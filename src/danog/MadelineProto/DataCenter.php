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

namespace danog\MadelineProto;

/**
 * Manages datacenters.
 */
class DataCenter
{
    use \danog\MadelineProto\Tools;

    public $sockets = [];
    public $curdc = 0;
    public $dclist = [];
    public $settings = [];

    public function __construct(&$dclist, &$settings)
    {
        $this->dclist = &$dclist;
        $this->settings = &$settings;
        foreach ($this->sockets as &$socket) {
            $socket->close_and_reopen();
        }
    }

    public function dc_disconnect($dc_number)
    {
        if ($this->curdc === $dc_number) {
            $this->curdc = 0;
        }
        if (isset($this->sockets[$dc_number])) {
            \danog\MadelineProto\Logger::log(['Disconnecting from DC '.$dc_number.'...'], \danog\MadelineProto\Logger::VERBOSE);
            unset($this->sockets[$dc_number]);
        }
    }

    public function dc_connect($dc_number, $settings = [])
    {
        $this->curdc = $dc_number;
        if (isset($this->sockets[$dc_number])) {
            return false;
        }

        if ($settings === []) {
            $settings = $this->settings[$dc_number];
        }
        $test = $settings['test_mode'] ? 'test' : 'main';
        $ipv6 = $settings['ipv6'] ? 'ipv6' : 'ipv4';
        $address = $this->dclist[$test][$ipv6][$dc_number]['ip_address'];
        $address = $settings['ipv6'] ? '['.$address.']' : $address;
        $port = $this->dclist[$test][$ipv6][$dc_number]['port'];
        if ($settings['protocol'] === 'https') {
            $subdomain = $this->dclist['ssl_subdomains'][$dc_number];
            $path = $settings['test_mode'] ? 'apiw_test1' : 'apiw1';
            $address = $settings['protocol'].'://'.$subdomain.'.web.telegram.org/'.$path;
        }

        if ($settings['protocol'] === 'http') {
            $address = $settings['protocol'].'://'.$address.'/api';
            $port = 80;
        }
        \danog\MadelineProto\Logger::log(['Connecting to DC '.$dc_number.' ('.$test.' server, '.$ipv6.', '.$settings['protocol'].')...'], \danog\MadelineProto\Logger::VERBOSE);

        $this->sockets[$dc_number] = new Connection($address, $port, $settings['protocol'], $settings['timeout']);

        return true;
    }

    public function &__get($name)
    {
        return $this->sockets[$this->curdc]->{$name};
    }

    public function __set($name, $value)
    {
        $this->sockets[$this->curdc]->{$name} = &$value;
    }

    public function __isset($name)
    {
        return isset($this->sockets[$this->curdc]->{$name});
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->sockets[$this->curdc], $name], $arguments);
    }
}
