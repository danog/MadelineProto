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
use danog\MadelineProto\Stream\Common\BufferedRawStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoTransport\AbridgedStream;
use danog\MadelineProto\Stream\MTProtoTransport\FullStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpsStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpStream;
use danog\MadelineProto\Stream\MTProtoTransport\IntermediatePaddedStream;
use danog\MadelineProto\Stream\MTProtoTransport\IntermediateStream;
use danog\MadelineProto\Stream\MTProtoTransport\ObfuscatedStream;
use danog\MadelineProto\Stream\Proxy\HttpProxy;
use danog\MadelineProto\Stream\Proxy\SocksProxy;
use danog\MadelineProto\Stream\Transport\DefaultStream;
use danog\MadelineProto\Stream\Transport\WssStream;
use danog\MadelineProto\Stream\Transport\WsStream;

/**
 * Manages datacenters.
 */
class DataCenter
{
    use \danog\MadelineProto\Tools;
    use \danog\Serializable;
    public $sockets = [];
    public $curdc = 0;
    private $API;
    private $dclist = [];
    private $settings = [];

    public function __sleep()
    {
        return ['sockets', 'curdc', 'dclist', 'settings'];
    }

    public function __magic_construct($API, $dclist, $settings)
    {
        $this->API = $API;
        $this->dclist = $dclist;
        $this->settings = $settings;
        foreach ($this->sockets as $key => $socket) {
            if ($socket instanceof Connection) {
                $this->API->logger->logger(sprintf(\danog\MadelineProto\Lang::$current_lang['dc_con_stop'], $key), \danog\MadelineProto\Logger::VERBOSE);
                $socket->old = true;
                $socket->disconnect();
            } else {
                unset($this->sockets[$key]);
            }
        }
    }

    public function dc_connect($dc_number)
    {
        return $this->wait($this->dc_connect_async($dc_number));
    }

    public function dc_connect_async($dc_number): \Generator
    {
        if (isset($this->sockets[$dc_number]) && !isset($this->sockets[$dc_number]->old)) {
            return false;
        }
        $ctxs = $this->generate_contexts($dc_number);
        if (empty($ctxs)) {
            return false;
        }
        foreach ($ctxs as $ctx) {
            try {
                if (isset($this->sockets[$dc_number]->old)) {
                    $this->sockets[$dc_number]->setExtra($this->API);
                    yield $this->sockets[$dc_number]->connect($ctx);
                } else {
                    $this->sockets[$dc_number] = new Connection();
                    $this->sockets[$dc_number]->setExtra($this->API);
                    yield $this->sockets[$dc_number]->connect($ctx);
                }
                $this->API->logger->logger('OK!', \danog\MadelineProto\Logger::WARNING);

                return true;
            } catch (\Throwable $e) {
                $this->API->logger->logger('Connection failed: '.$e->getMessage(), \danog\MadelineProto\Logger::ERROR);
            } catch (\Exception $e) {
                $this->API->logger->logger('Connection failed: '.$e->getMessage(), \danog\MadelineProto\Logger::ERROR);
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
                $default = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [AbridgedStream::getName(), []]];
                break;
            case 'tcp_intermediate':
                $default = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [IntermediateStream::getName(), []]];
                break;
            case 'obfuscated2':
                $this->settings[$dc_config_number]['protocol'] = 'tcp_intermediate_padded';
                $this->settings[$dc_config_number]['obfuscated'] = true;
            case 'tcp_intermediate_padded':
                $default = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [IntermediatePaddedStream::getName(), []]];
                break;
            case 'tcp_full':
                $default = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [FullStream::getName(), []]];
                break;
            case 'http':
                $default = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [HttpStream::getName(), []]];
                break;
            case 'https':
                $default = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [HttpsStream::getName(), []]];
                break;
            default:
                throw new Exception(\danog\MadelineProto\Lang::$current_lang['protocol_invalid']);
        }
        if ($this->settings[$dc_config_number]['obfuscated'] && !in_array($default[1][0], [HttpsStream::getName(), HttpStream::getName()])) {
            $default = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [ObfuscatedStream::getName(), []], end($default)];
        }
        if ($this->settings[$dc_config_number]['transport'] && !in_array($default[1][0], [HttpsStream::getName(), HttpStream::getName()])) {
            switch ($this->settings[$dc_config_number]['transport']) {
                case 'tcp':
                    if ($this->settings[$dc_config_number]['obfuscated']) {
                        $default = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [ObfuscatedStream::getName(), []], end($default)];
                    }
                    break;
                case 'wss':
                    if ($this->settings[$dc_config_number]['obfuscated']) {
                        $default = [[DefaultStream::getName(), []], [WssStream::getName(), []], [BufferedRawStream::getName(), []], [ObfuscatedStream::getName(), []], end($default)];
                    } else {
                        $default = [[DefaultStream::getName(), []], [WssStream::getName(), []], [BufferedRawStream::getName(), []], end($default)];
                    }
                    break;
                case 'ws':
                    if ($this->settings[$dc_config_number]['obfuscated']) {
                        $default = [[DefaultStream::getName(), []], [WsStream::getName(), []], [BufferedRawStream::getName(), []], [ObfuscatedStream::getName(), []], end($default)];
                    } else {
                        $default = [[DefaultStream::getName(), []], [WsStream::getName(), []], [BufferedRawStream::getName(), []], end($default)];
                    }
                    break;
            }
        }
        $combos[] = $default;

        if (!isset($this->settings[$dc_config_number]['do_not_retry'])) {
            if ((isset($this->dclist[$test][$ipv6][$dc_number]['tcpo_only']) && $this->dclist[$test][$ipv6][$dc_number]['tcpo_only']) || isset($this->dclist[$test][$ipv6][$dc_number]['secret'])) {
                $extra = isset($this->dclist[$test][$ipv6][$dc_number]['secret']) ? ['secret' => $this->dclist[$test][$ipv6][$dc_number]['secret']] : [];
                $combos[] = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [ObfuscatedStream::getName(), $extra], [IntermediatePaddedStream::getName(), []]];
            }

            if (is_array($this->settings[$dc_config_number]['proxy'])) {
                $proxies = $this->settings[$dc_config_number]['proxy'];
                $proxy_extras = $this->settings[$dc_config_number]['proxy_extra'];
            } else {
                $proxies = [$this->settings[$dc_config_number]['proxy']];
                $proxy_extras = [$this->settings[$dc_config_number]['proxy_extra']];
            }
            foreach ($proxies as $key => $proxy) {
                // Convert old settings
                if ($proxy === '\\Socket') {
                    $proxy = DefaultStream::getName();
                }
                if ($proxy === '\\SocksProxy') {
                    $proxy = SocksProxy::getName();
                }
                if ($proxy === '\\HttpProxy') {
                    $proxy = HttpProxy::getName();
                }
                if ($proxy === '\\MTProxySocket') {
                    $proxy = ObfuscatedStream::getName();
                }
                if ($proxy === DefaultStream::getName()) {
                    continue;
                }
                $extra = $proxy_extras[$key];
                if (!isset(class_implements($proxy)['danog\\MadelineProto\\Stream\\StreamInterface'])) {
                    throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['proxy_class_invalid']);
                }
                if ($proxy === ObfuscatedStream::getName() && in_array(strlen($extra['secret']), [17, 34])) {
                    $combos []= [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [$proxy, $extra], [IntermediatePaddedStream::getName(), []]];
                }
                foreach ($combos as $k => $orig) {
                    $combo = [];
                    if ($proxy === ObfuscatedStream::getName()) {
                        $combo = $orig;
                        if ($combo[count($combo) - 2][0] === ObfuscatedStream::getName()) {
                            $combo[count($combo) - 2][1] = $extra;
                        } else {
                            $mtproto = end($combo);
                            $combo[count($combo) - 1] = [$proxy, $extra];
                            $combo[] = $mtproto;
                        }
                    } else {
                        if ($orig[1][0] === BufferedRawStream::getName()) {
                            list($first, $second) = [array_slice($orig, 0, 2), array_slice($orig, 2)];
                            $first[] = [$proxy, $extra];
                            $combo = array_merge($first, $second);
                        } elseif ($orig[1][0] === WssStream::getName()) {
                            list($first, $second) = [array_slice($orig, 0, 1), array_slice($orig, 1)];
                            $first[] = [BufferedRawStream::getName(), []];
                            $first[] = [$proxy, $extra];
                            $combo = array_merge($first, $second);
                        }
                    }

                    array_unshift($combos, $combo);
                    //unset($combos[$k]);
                }
            }

            $combos[] = [[DefaultStream::getName(), []], [BufferedRawStream::getName(), []], [HttpsStream::getName(), []]];
            $combos = array_unique($combos, SORT_REGULAR);
        }
        /* @var $context \Amp\ClientConnectContext */
        $context = (new ClientConnectContext())->withMaxAttempts(1)->withConnectTimeout(1000 * $this->settings[$dc_config_number]['timeout']);

        foreach ($combos as $combo) {
            $ipv6 = [$this->settings[$dc_config_number]['ipv6'] ? 'ipv6' : 'ipv4', $this->settings[$dc_config_number]['ipv6'] ? 'ipv4' : 'ipv6'];

            foreach ($ipv6 as $ipv6) {
                if (!isset($this->dclist[$test][$ipv6][$dc_number]['ip_address'])) {
                    continue;
                }


                $address = $this->dclist[$test][$ipv6][$dc_number]['ip_address'];
                $port = $this->dclist[$test][$ipv6][$dc_number]['port'];

                foreach (array_unique([$port, 443, 80, 88, 5222]) as $port) {
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

                    if ($combo[1][0] === WssStream::getName()) {
                        $subdomain = $this->dclist['ssl_subdomains'][preg_replace('/\D+/', '', $dc_number)];
                        if (strpos($dc_number, '_media') !== false) {
                            $subdomain .= '-1';
                        }
                        $path = $this->settings[$dc_config_number]['test_mode'] ? 'apiws_test' : 'apiws';

                        $uri = 'tcp://'.$subdomain.'.web.telegram.org:'.$port.'/'.$path;
                    } elseif ($combo[1][0] === WsStream::getName()) {
                        $subdomain = $this->dclist['ssl_subdomains'][preg_replace('/\D+/', '', $dc_number)];
                        if (strpos($dc_number, '_media') !== false) {
                            $subdomain .= '-1';
                        }
                        $path = $this->settings[$dc_config_number]['test_mode'] ? 'apiws_test' : 'apiws';

                        //$uri = 'tcp://' . $subdomain . '.web.telegram.org:' . $port . '/' . $path;
                        $uri = 'tcp://'.$address.':'.$port.'/'.$path;
                    }

                    /** @var $ctx \danog\MadelineProto\Stream\ConnectionContext */
                    $ctx = (new ConnectionContext())
                        ->setDc($dc_number)
                        ->setTest($this->settings[$dc_config_number]['test_mode'])
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

        if (empty($ctxs)) {
            unset($this->sockets[$dc_number]);

            $this->API->logger->logger("No info for DC $dc_number", \danog\MadelineProto\Logger::ERROR);

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
