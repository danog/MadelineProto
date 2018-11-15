<?php

/**
 * DataCenter module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use function Amp\call;
use function Amp\Promise\wait;
use function Amp\Socket\connect;

/**
 * Manages datacenters.
 */
class DataCenter
{
    use \danog\MadelineProto\Tools;
    use \danog\Serializable;
    public $sockets = [];
    public $curdc = 0;
    private $dclist = [];
    private $settings = [];

    public function __sleep()
    {
        return ['sockets', 'curdc', 'dclist', 'settings'];
    }

    public function __magic_construct($dclist, $settings)
    {
        $this->dclist = $dclist;
        $this->settings = $settings;
        foreach ($this->sockets as $key => $socket) {
            if ($socket instanceof Connection) {
                \danog\MadelineProto\Logger::log(sprintf(\danog\MadelineProto\Lang::$current_lang['dc_con_stop'], $key), \danog\MadelineProto\Logger::VERBOSE);
                $socket->old = true;
            //wait($socket->close());
            } else {
                unset($this->sockets[$key]);
            }
        }
    }

    public function dc_connect($dc_number)
    {
        return call(
            function () use ($dc_number) {
                if (isset($this->sockets[$dc_number]) && !isset($this->sockets[$dc_number]->old)) {
                    return false;
                }
                $dc_config_number = isset($this->settings[$dc_number]) ? $dc_number : 'all';
                $test = $this->settings[$dc_config_number]['test_mode'] ? 'test' : 'main';
                $x = 0;
                do {
                    $ipv6 = $this->settings[$dc_config_number]['ipv6'] ? 'ipv6' : 'ipv4';
                    if (!isset($this->dclist[$test][$ipv6][$dc_number]['ip_address'])) {
                        unset($this->sockets[$dc_number]);

                        \danog\MadelineProto\Logger::log("No info for DC $dc_number", \danog\MadelineProto\Logger::ERROR);

                        return false;
                    }
                    $address = $this->dclist[$test][$ipv6][$dc_number]['ip_address'];
                    $port = $this->dclist[$test][$ipv6][$dc_number]['port'];
                    if (isset($this->dclist[$test][$ipv6][$dc_number]['tcpo_only']) && $this->dclist[$test][$ipv6][$dc_number]['tcpo_only']) {
                        if ($dc_config_number === 'all') {
                            $dc_config_number = $dc_number;
                        }
                        if (!isset($this->settings[$dc_config_number])) {
                            $this->settings[$dc_config_number] = $this->settings['all'];
                        }
                        $this->settings[$dc_config_number]['protocol'] = 'obfuscated2';
                    }
                    if (isset($this->dclist[$test][$ipv6][$dc_number]['secret'])) {
                        if ($dc_config_number === 'all') {
                            $dc_config_number = $dc_number;
                        }
                        if (!isset($this->settings[$dc_config_number])) {
                            $this->settings[$dc_config_number] = $this->settings['all'];
                        }
                        $this->settings[$dc_config_number]['protocol'] = 'obfuscated2';
                        if ($this->settings[$dc_config_number]['proxy_extra'] === null) {
                            $this->settings[$dc_config_number]['proxy_extra'] = [];
                        }
                        $this->settings[$dc_config_number]['proxy_extra']['secret'] = $this->dclist[$test][$ipv6][$dc_number]['secret'];
                    }
                    if ($ipv6) {
                        $address = '['.$address.']';
                    }

                    if (strpos($this->settings[$dc_config_number]['protocol'], 'https') === 0) {
                        $port = 443;
                        $subdomain = $this->dclist['ssl_subdomains'][preg_replace('/\D+/', '', $dc_number)];
                        if (strpos($dc_number, '_media') !== false) {
                            $subdomain .= '-1';
                        }
                        $path = $this->settings[$dc_config_number]['test_mode'] ? 'apiw_test1' : 'apiw1';
                    }

                    \danog\MadelineProto\Logger::log(sprintf(\danog\MadelineProto\Lang::$current_lang['dc_con_test_start'], $dc_number, $test, $ipv6, $this->settings[$dc_config_number]['protocol']), \danog\MadelineProto\Logger::VERBOSE);
                    foreach (array_unique([$port, 443, 80, 88]) as $port) {
                        \danog\MadelineProto\Logger::log('Trying connection on port '.$port.' of '.$address.'...', \danog\MadelineProto\Logger::WARNING);

                        if (strpos($this->settings[$dc_config_number]['protocol'], 'https') === 0) {
                            $address = 'https://'.$subdomain.'.web.telegram.org:'.$port.'/'.$path;
                        } elseif ($this->settings[$dc_config_number]['protocol'] === 'http') {
                            $address = $this->settings[$dc_config_number]['protocol'].'://'.$address.':'.$port.'/api';
                        } else {
                            $address = 'tcp://'.$address.':'.$port;
                        }

                        try {
                            if (isset($this->sockets[$dc_number]->old)) {
                                yield $this->sockets[$dc_number]->connect($dc_number, $this->settings[$dc_config_number]['proxy'], $this->settings[$dc_config_number]['proxy_extra'], $address, $this->settings[$dc_config_number]['protocol'], $this->settings[$dc_config_number]['timeout'], $this->settings[$dc_config_number]['ipv6']);
                                unset($this->sockets[$dc_number]->old);
                            } else {
                                $this->sockets[$dc_number] = new Connection();
                                yield $this->sockets[$dc_number]->connect($dc_number, $this->settings[$dc_config_number]['proxy'], $this->settings[$dc_config_number]['proxy_extra'], $address, $port, $this->settings[$dc_config_number]['protocol'], $this->settings[$dc_config_number]['timeout'], $this->settings[$dc_config_number]['ipv6']);
                            }
                            \danog\MadelineProto\Logger::log('OK!', \danog\MadelineProto\Logger::WARNING);

                            return true;
                        } catch (\Throwable $e) {
                            \danog\MadelineProto\Logger::log('Connection failed: '.$e->getMessage(), \danog\MadelineProto\Logger::ERROR);
                        }
                        if (isset($this->settings[$dc_config_number]['do_not_retry']) && $this->settings[$dc_config_number]['do_not_retry']) {
                            break;
                        }
                    }
                    switch ($x) {
                        case 0:
                            $this->settings[$dc_config_number]['ipv6'] = !$this->settings[$dc_config_number]['ipv6'];
                            \danog\MadelineProto\Logger::log('Connection failed, retrying connection with '.($this->settings[$dc_config_number]['ipv6'] ? 'ipv6' : 'ipv4').'...', \danog\MadelineProto\Logger::WARNING);
                            continue;
                        case 1:
                            if (isset($this->dclist[$test][$ipv6][$dc_number.'_bk']['ip_address'])) {
                                $dc_number .= '_bk';
                            }
                            \danog\MadelineProto\Logger::log('Connection failed, retrying connection on backup DCs with '.($this->settings[$dc_config_number]['ipv6'] ? 'ipv6' : 'ipv4').'...', \danog\MadelineProto\Logger::WARNING);
                            continue;
                        case 2:
                            $this->settings[$dc_config_number]['ipv6'] = !$this->settings[$dc_config_number]['ipv6'];
                            \danog\MadelineProto\Logger::log('Connection failed, retrying connection on backup DCs with '.($this->settings[$dc_config_number]['ipv6'] ? 'ipv6' : 'ipv4').'...', \danog\MadelineProto\Logger::WARNING);
                            continue;
                        case 3:
                            $this->settings[$dc_config_number]['proxy'] = '\\Socket';
                            \danog\MadelineProto\Logger::log('Connection failed, retrying connection without the proxy with '.($this->settings[$dc_config_number]['ipv6'] ? 'ipv6' : 'ipv4').'...', \danog\MadelineProto\Logger::WARNING);
                            continue;
                        case 4:
                            $this->settings[$dc_config_number]['ipv6'] = !$this->settings[$dc_config_number]['ipv6'];
                            \danog\MadelineProto\Logger::log('Connection failed, retrying connection without the proxy with '.($this->settings[$dc_config_number]['ipv6'] ? 'ipv6' : 'ipv4').'...', \danog\MadelineProto\Logger::WARNING);
                            continue;
                        case 5:
                            $this->settings[$dc_config_number]['proxy'] = '\\HttpProxy';
                            $this->settings[$dc_config_number]['proxy_extra'] = ['address' => 'localhost', 'port' => 80];
                            $this->settings[$dc_config_number]['ipv6'] = !$this->settings[$dc_config_number]['ipv6'];
                            \danog\MadelineProto\Logger::log('Connection failed, retrying connection with localhost HTTP proxy with '.($this->settings[$dc_config_number]['ipv6'] ? 'ipv6' : 'ipv4').'...', \danog\MadelineProto\Logger::WARNING);
                            continue;
                        case 6:
                            $this->settings[$dc_config_number]['ipv6'] = !$this->settings[$dc_config_number]['ipv6'];
                            \danog\MadelineProto\Logger::log('Connection failed, retrying connection with localhost HTTP proxy with '.($this->settings[$dc_config_number]['ipv6'] ? 'ipv6' : 'ipv4').'...', \danog\MadelineProto\Logger::WARNING);
                            continue;
                        default:
                            throw new \danog\MadelineProto\Exception("Could not connect to DC $dc_number");
                    }
                } while (++$x);

                throw new \danog\MadelineProto\Exception("Could not connect to DC $dc_number");
            }
        );
    }

    public function get_dcs($all = true)
    {
        $test = $this->settings['all']['test_mode'] ? 'test' : 'main';
        $ipv6 = $this->settings['all']['ipv6'] ? 'ipv6' : 'ipv4';

        return $all ? array_keys((array) $this->dclist[$test][$ipv6]) : array_keys((array) $this->sockets);
    }
}
