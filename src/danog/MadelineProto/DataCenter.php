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

use Amp\Socket\ClientConnectContext;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoTransport\AbridgedStream;
use danog\MadelineProto\Stream\MTProtoTransport\FullStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpsStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpStream;
use danog\MadelineProto\Stream\MTProtoTransport\IntermediateStream;
use danog\MadelineProto\Stream\MTProtoTransport\ObfuscatedStream;
use danog\MadelineProto\Stream\Transport\DefaultStream;
use danog\MadelineProto\Stream\Transport\ObfuscatedTransportStream;
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
            //wait($socket->end());
            } else {
                unset($this->sockets[$key]);
            }
        }
    }

    public function dc_connect($dc_number)
    {
        return wait(call([$this, 'dc_connect_async'], $dc_number));
    }

    public function dc_connect_async($dc_number)
    {
        if (isset($this->sockets[$dc_number]) && !isset($this->sockets[$dc_number]->old)) {
            return false;
        }
        $ctxs = $this->generate_contexts($dc_number);
        foreach ($ctxs as $ctx) {
            \danog\MadelineProto\Logger::log("Trying connection via $ctx", \danog\MadelineProto\Logger::WARNING);

            try {
                if (isset($this->sockets[$dc_number]->old)) {
                    yield $this->sockets[$dc_number]->connect($ctx);
                    unset($this->sockets[$dc_number]->old);
                } else {
                    $this->sockets[$dc_number] = new Connection();
                    yield $this->sockets[$dc_number]->connect($ctx);
                }
                \danog\MadelineProto\Logger::log('OK!', \danog\MadelineProto\Logger::WARNING);

                return true;
            } catch (\Throwable $e) {
                \danog\MadelineProto\Logger::log('Connection failed: '.$e->getMessage(), \danog\MadelineProto\Logger::ERROR);
            }
        }

        throw new \danog\MadelineProto\Exception("Could not connect to DC $dc_number");
    }

    public function generate_contexts($dc_number)
    {
        $ctxs = [];
        $combos = [];

        $dc_config_number = isset($this->settings[$dc_number]) ? $dc_number : 'all';
        $test = $this->settings[$dc_config_number]['test_mode'] ? 'test' : 'main';
        $ipv6 = $this->settings[$dc_config_number]['ipv6'] ? 'ipv6' : 'ipv4';

        switch ($this->settings[$dc_config_number]['protocol']) {
            case 'tcp_abridged':
                $default = [[DefaultStream::getName(), []], [AbridgedStream::getName(), []]];
                break;
            case 'tcp_intermediate':
                $default = [[DefaultStream::getName(), []], [IntermediateStream::getName(), []]];
                break;
            case 'tcp_full':
                $default = [[DefaultStream::getName(), []], [FullStream::getName(), []]];
                break;
            case 'http':
                $default = [[DefaultStream::getName(), []], [HttpStream::getName(), []]];
                break;
            case 'https':
                $default = [[DefaultStream::getName(), []], [HttpsStream::getName(), []]];
                break;
            case 'obfuscated2':
                $default = [[ObfuscatedTransportStream::getName()]];
                break;
            default:
                throw new Exception(\danog\MadelineProto\Lang::$current_lang['protocol_invalid']);
        }
        $combos[] = $default;

        if (!isset($this->settings[$dc_config_number]['do_not_retry'])) {
            if ((isset($this->dclist[$test][$ipv6][$dc_number]['tcpo_only']) && $this->dclist[$test][$ipv6][$dc_number]['tcpo_only']) || isset($this->dclist[$test][$ipv6][$dc_number]['secret'])) {
                $extra = isset($this->dclist[$test][$ipv6][$dc_number]['secret']) ? ['secret' => $this->dclist[$test][$ipv6][$dc_number]['secret']] : [];
                $combos[] = [[ObfuscatedTransportStream::getName(), $extra]];
            }

            // Convert old settings
            if ($this->settings[$dc_config_number]['proxy'] === '\\Socket') {
                $this->settings[$dc_config_number]['proxy'] = DefaultStream::getName();
            }
            if ($this->settings[$dc_config_number]['proxy'] === '\\MTProxySocket') {
                $this->settings[$dc_config_number]['proxy'] = ObfuscatedTransportStream::getName();
            }
            if (is_array($this->settings[$dc_config_number]['proxy'])) {
                $proxies = $this->settings[$dc_config_number]['proxy'];
                $proxy_extras = $this->settings[$dc_config_number]['proxy_extra'];
            } else {
                $proxies = [$this->settings[$dc_config_number]['proxy']];
                $proxy_extras = [$this->settings[$dc_config_number]['proxy_extra']];
            }
            foreach ($proxies as $key => $proxy) {
                $extra = $proxy_extras[$key];
                if (!isset(class_implements($proxy)['danog\\MadelineProto\\Stream\\StreamInterface'])) {
                    throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['proxy_class_invalid']);
                }
                foreach ($combos as $orig) {
                    $combo = [];
                    if (isset(class_implements($proxy)['danog\\MadelineProto\\Stream\\MTProtoBufferInterface'])) {
                        $combo[] = [$proxy, $extra];
                    } else {
                        if (!isset(class_implements($proxy)['danog\\MadelineProto\\Stream\\RawStreamInterface'])) {
                            $combo[] = [DefaultStream::getName()];
                        }
                        if (isset(class_implements($proxy)['danog\\MadelineProto\\Stream\\BufferedStreamInterface'])) {
                            $combo[] = [$proxy, $extra];
                        }
                        $default_protocol = end($orig);
                        if ($default_protocol[0] === ObfuscatedTransportStream::getName()) {
                            $default_protocol[0] = ObfuscatedStream::getName();
                        }
                        $combo[] = $default_protocol;
                    }
                    $combos[] = $combo;
                }
            }

            $combos[] = [[DefaultStream::getName(), []], [HttpsStream::getName(), []]];
            $combos = array_unique($combos, SORT_REGULAR);
        }
        /* @var $context \Amp\ClientConnectContext */
        $context = (new ClientConnectContext())->withMaxAttempts(1)->withConnectTimeout(1000 * $this->settings[$dc_config_number]['timeout']);

        foreach ($combos as $combo) {
            $ipv6 = [$this->settings[$dc_config_number]['ipv6'] ? 'ipv6' : 'ipv4', $this->settings[$dc_config_number]['ipv6'] ? 'ipv4' : 'ipv6'];

            foreach ($ipv6 as $ipv6) {
                if (!isset($this->dclist[$test][$ipv6][$dc_number]['ip_address'])) {
                    unset($this->sockets[$dc_number]);

                    \danog\MadelineProto\Logger::log("No info for DC $dc_number", \danog\MadelineProto\Logger::ERROR);

                    continue;
                }
                $address = $this->dclist[$test][$ipv6][$dc_number]['ip_address'];
                $port = $this->dclist[$test][$ipv6][$dc_number]['port'];
                if ($ipv6 === 'ipv6') {
                    $address = '['.$address.']';
                }

                foreach (array_unique([$port, 443, 80, 88]) as $port) {
                    $stream = end($combo)[0];

                    if ($stream === HttpsStream::getName()) {
                        $subdomain = $this->dclist['ssl_subdomains'][preg_replace('/\D+/', '', $dc_number)];
                        if (strpos($dc_number, '_media') !== false) {
                            $subdomain .= '-1';
                        }
                        $path = $this->settings[$dc_config_number]['test_mode'] ? 'apiw_test1' : 'apiw1';

                        $uri = 'tcp://'.$subdomain.'.web.telegram.org:'.$port.'/'.$path;
                    } elseif ($stream === HttpStream::getName()) {
                        $uri = 'tcp://'.$address.':'.$port.'/api';
                    } else {
                        $uri = 'tcp://'.$address.':'.$port;
                    }

                    /** @var $ctx \danog\MadelineProto\Stream\ConnectionContext */
                    $ctx = (new ConnectionContext())
                        ->setDc($dc_number)
                        ->setSocketContext($context)
                        ->setUri($uri)
                        ->setIpv6($ipv6 === 'ipv6');

                    foreach ($combo as $stream) {
                        $ctx->addStream(...$stream);
                    }
                    $ctxs[] = $ctx;
                }
            }
        }

        if (isset($this->dclist[$test][$ipv6][$dc_number.'_bk']['ip_address'])) {
            $ctxs = array_merge($ctxs, $this->generate_contexts($dc_number.'_bk'));
        }

        return $ctxs;
    }

    public function get_dcs($all = true)
    {
        $test = $this->settings['all']['test_mode'] ? 'test' : 'main';
        $ipv6 = $this->settings['all']['ipv6'] ? 'ipv6' : 'ipv4';

        return $all ? array_keys((array) $this->dclist[$test][$ipv6]) : array_keys((array) $this->sockets);
    }
}
