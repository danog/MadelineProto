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
    use \danog\Serializable;

    public $sockets = [];
    public $curdc = 0;
    public $dclist = [];
    public $settings = [];

    /*
        public $i = [];
        public function __get($name) {
            echo "GETTING $name\n";
            if (isset($this->i[$name]) && $this->{$name} === null) var_dump($this->i[$name]);
            if ($this->{$name} instanceof \Volatile) $this->i[$name] = debug_backtrace(0);
    var_dump(is_null($this->{$name}));
            return $this->{$name};
        }
    */
    public function __sleep()
    {
        return ['sockets', 'curdc', 'dclist', 'settings'];
    }

    public function ___construct($dclist, $settings)
    {
        $this->dclist = $dclist;
        $this->settings = $settings;
        foreach ($this->sockets as $key => $socket) {
            if ($socket instanceof Connection) {
                \danog\MadelineProto\Logger::log(['Connecting to DC '.$key.'...'], \danog\MadelineProto\Logger::VERBOSE);
                $socket->old = true;
                $socket->close_and_reopen();
            } else {
                unset($this->sockets[$key]);
            }
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

    public function dc_connect($dc_number)
    {
        $this->curdc = $dc_number;
        if (isset($this->sockets[$dc_number]) && !isset($this->sockets[$dc_number]->old)) {
            return false;
        }
        $dc_config_number = isset($this->settings[$dc_number]) ? $dc_number : 'all';
        $test = $this->settings[$dc_config_number]['test_mode'] ? 'test' : 'main';
        $ipv6 = $this->settings[$dc_config_number]['ipv6'] ? 'ipv6' : 'ipv4';
        $address = $this->dclist[$test][$ipv6][$dc_number]['ip_address'];
        //$address = $this->settings[$dc_config_number]['ipv6'] ? '['.$address.']' : $address;
        $port = $this->dclist[$test][$ipv6][$dc_number]['port'];
        if ($this->settings[$dc_config_number]['protocol'] === 'https') {
            $subdomain = $this->dclist['ssl_subdomains'][$dc_number];
            $path = $this->settings[$dc_config_number]['test_mode'] ? 'apiw_test1' : 'apiw1';
            $address = $this->settings[$dc_config_number]['protocol'].'://'.$subdomain.'.web.telegram.org/'.$path;
        }

        if ($this->settings[$dc_config_number]['protocol'] === 'http') {
            $address = $this->settings[$dc_config_number]['protocol'].'://'.$address.'/api';
            $port = 80;
        }
        \danog\MadelineProto\Logger::log(['Connecting to DC '.$dc_number.' ('.$test.' server, '.$ipv6.', '.$this->settings[$dc_config_number]['protocol'].')...'], \danog\MadelineProto\Logger::VERBOSE);

        if (isset($this->sockets[$dc_number]->old)) {
            $this->sockets[$dc_number]->__construct($this->settings[$dc_config_number]['proxy'], $this->settings[$dc_config_number]['proxy_extra'], $address, $port, $this->settings[$dc_config_number]['protocol'], $this->settings[$dc_config_number]['timeout'], $this->settings[$dc_config_number]['ipv6']);
            unset($this->sockets[$dc_number]->old);
        } else {
            $this->sockets[$dc_number] = new Connection($this->settings[$dc_config_number]['proxy'], $this->settings[$dc_config_number]['proxy_extra'], $address, $port, $this->settings[$dc_config_number]['protocol'], $this->settings[$dc_config_number]['timeout'], $this->settings[$dc_config_number]['ipv6']);
        }

        return true;
    }

    public function get_dcs($all = true)
    {
        $test = $this->settings['all']['test_mode'] ? 'test' : 'main';
        $ipv6 = $this->settings['all']['ipv6'] ? 'ipv6' : 'ipv4';

        return $all ? array_keys((array) $this->dclist[$test][$ipv6]) : array_keys((array) $this->sockets);
    }

    /*
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
    }*/
}
