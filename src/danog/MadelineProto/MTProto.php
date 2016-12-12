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
 * Manages all of the mtproto stuff.
 */
class MTProto extends MTProtoTools
{
    public $settings = [];
    public $authorized = false;
    public $waiting_code = false;
    public $config = ['expires' => -1];
    public $ipv6 = false;
    public $bad_msg_error_codes = [
        16 => 'msg_id too low (most likely, client time is wrong; it would be worthwhile to synchronize it using msg_id notifications and re-send the original message with the “correct” msg_id or wrap it in a container with a new msg_id if the original message had waited too long on the client to be transmitted)',
        17 => 'msg_id too high (similar to the previous case, the client time has to be synchronized, and the message re-sent with the correct msg_id)',
        18 => 'incorrect two lower order msg_id bits (the server expects client message msg_id to be divisible by 4)',
        19 => 'container msg_id is the same as msg_id of a previously received message (this must never happen)',
        20 => 'message too old, and it cannot be verified whether the server has received a message with this msg_id or not',
        32 => 'msg_seqno too low (the server has already received a message with a lower msg_id but with either a higher or an equal and odd seqno)',
        33 => 'msg_seqno too high (similarly, there is a message with a higher msg_id but with either a lower or an equal and odd seqno)',
        34 => 'an even msg_seqno expected (irrelevant message), but odd received',
        35 => 'odd msg_seqno expected (relevant message), but even received',
        48 => 'incorrect server salt (in this case, the bad_server_salt response is received with the correct salt, and the message is to be re-sent with it)',
        64 => 'invalid container.',
    ];

    public function __construct($settings = [])
    {
        // Detect 64 bit
        if (PHP_INT_SIZE < 8) {
            throw new Exception('MadelineProto supports only 64 bit systems ATM');
        }

        // Detect ipv6
        $google = '';
        try {
            $google = file_get_contents('https://ipv6.google.com');
        } catch (Exception $e) {
        }
        $this->ipv6 = strlen($google) > 0;


        // Detect device model
        $device_model = 'Web server';
        try {
            $device_model = php_uname('s');
        } catch (Exception $e) {
        }


        // Detect system version
        $system_version = phpversion();
        try {
            $system_version = php_uname('r');
        } catch (Exception $e) {
        }

        // Set default settings
        $default_settings = [
            'authorization' => [ // Authorization settings
                'default_temp_auth_key_expires_in' => 31557600, // validity of temporary keys and the binding of the temporary and permanent keys
                'rsa_key'                          => '-----BEGIN RSA PUBLIC KEY-----
MIIBCgKCAQEAwVACPi9w23mF3tBkdZz+zwrzKOaaQdr01vAbU4E1pvkfj4sqDsm6
lyDONS789sVoD/xCS9Y0hkkC3gtL1tSfTlgCMOOul9lcixlEKzwKENj1Yz/s7daS
an9tqw3bfUV/nqgbhGX81v/+7RFAEd+RwFnK7a+XYl9sluzHRyVVaTTveB2GazTw
Efzk2DWgkBluml8OREmvfraX3bkHZJTKX4EQSjBbbdJ2ZXIsRrYOXfaA+xayEGB+
8hdlLmAjbCVfaigxX0CDqWeR1yFL9kwd9P0NsZRPsmoqVwMbMu7mStFai6aIhc3n
Slv8kg9qv1m6XHVQY3PnEw+QQtqSIXklHwIDAQAB
-----END RSA PUBLIC KEY-----', // RSA public key
            ],
            'connection' => [ // List of datacenters/subdomains where to connect
                'ssl_subdomains' => [ // Subdomains of web.telegram.org for https protocol
                    1 => 'pluto',
                    2 => 'venus',
                    3 => 'aurora',
                    4 => 'vesta',
                    5 => 'flora', // musa oh wait no :(
                ],
                'test' => [ // Test datacenters
                    'ipv4' => [ // ipv4 addresses
                        2 => [ // The rest will be fetched using help.getConfig
                            'ip_address' => '149.154.167.40',
                            'port'       => 443,
                            'media_only' => false,
                            'tcpo_only'  => false,
                        ],
                     ],
                    'ipv6' => [ // ipv6 addresses
                        2 => [ // The rest will be fetched using help.getConfig
                            'ip_address' => '2001:067c:04e8:f002:0000:0000:0000:000e',
                            'port'       => 443,
                            'media_only' => false,
                            'tcpo_only'  => false,
                         ],
                     ],
                ],
                'main' => [ // Main datacenters
                    'ipv4' => [ // ipv4 addresses
                        2 => [ // The rest will be fetched using help.getConfig
                            'ip_address' => '149.154.167.51',
                            'port'       => 443,
                            'media_only' => false,
                            'tcpo_only'  => false,
                         ],
                     ],
                    'ipv6' => [ // ipv6 addresses
                        2 => [ // The rest will be fetched using help.getConfig
                            'ip_address' => '2001:067c:04e8:f002:0000:0000:0000:000a',
                            'port'       => 443,
                            'media_only' => false,
                            'tcpo_only'  => false,
                         ],
                     ],
                ],
            ],
            'connection_settings' => [ // connection settings
                'all' => [ // These settings will be applied on every datacenter that hasn't a custom settings subarray...
                    'protocol'     => 'tcp_full', // can be tcp_full, tcp_abridged, tcp_intermediate, http (unsupported), https (unsupported), udp (unsupported)
                    'test_mode'    => false, // decides whether to connect to the main telegram servers or to the testing servers (deep telegram)
                    'ipv6'         => $this->ipv6, // decides whether to use ipv6, ipv6 attribute of API attribute of API class contains autodetected boolean
                    'timeout'      => 10, // timeout for sockets
                ],
            ],
            'app_info' => [ // obtained in https://my.telegram.org
                'api_id'          => 25628,
                'api_hash'        => '1fe17cda7d355166cdaa71f04122873c',
                'device_model'    => $device_model,
                'system_version'  => $system_version,
                'app_version'     => 'Unicorn', // 🌚
                'lang_code'       => 'en',
            ],
            'tl_schema'     => [ // TL scheme files
                'layer'         => 57, // layer version
                'src'           => [
                    'mtproto'  => __DIR__.'/TL_mtproto_v1.json', // mtproto TL scheme
                    'telegram' => __DIR__.'/TL_telegram_v57.json', // telegram TL scheme
                ],
            ],
            'logger'       => [ // Logger settings
                /*
                 * logger modes:
                 * 0 - No logger
                 * 1 - Log to the default logger destination
                 * 2 - Log to file defined in second parameter
                 * 3 - Echo logs
                 */
                'logger'       => 1, // write to
                'logger_param' => '/tmp/MadelineProto.log',
                'logger'       => 3, // overwrite previous setting and echo logs
            ],
            'max_tries'         => [
                'query'         => 5, // How many times should I try to call a method or send an object before throwing an exception
                'authorization' => 5, // How many times should I try to generate an authorization key before throwing an exception
                'response'      => 5, // How many times should I try to get a response of a query before throwing an exception
            ],
            'msg_array_limit'        => [ // How big should be the arrays containing the incoming and outgoing messages?
                'incoming' => 30,
                'outgoing' => 30,
            ],
        ];
        foreach ($default_settings as $key => $param) {
            if (!isset($settings[$key])) {
                $settings[$key] = $param;
            }
            foreach ($param as $subkey => $subparam) {
                if (!isset($settings[$key][$subkey])) {
                    $settings[$key][$subkey] = $subparam;
                }
            }
        }
        if (isset($settings['connection_settings']['all'])) {
            foreach ($this->range(1, 6) as $n) {
                if (!isset($settings['connection_settings'][$n])) {
                    $settings['connection_settings'][$n] = $settings['connection_settings']['all'];
                }
            }
            unset($settings['connection_settings']['all']);
        }
        $this->settings = $settings;

        // Setup logger
        $this->setup_logger();

        // Connect to servers
        \danog\MadelineProto\Logger::log('Istantiating DataCenter...');
        $this->datacenter = new DataCenter($this->settings['connection'], $this->settings['connection_settings']);

        // Load rsa key
        \danog\MadelineProto\Logger::log('Loading RSA key...');
        $this->key = new RSA($settings['authorization']['rsa_key']);

        // Istantiate TL class
        \danog\MadelineProto\Logger::log('Translating tl schemas...');
        $this->tl = new TL\TL($this->settings['tl_schema']['src']);

        $this->switch_dc(2, true);
        $this->get_config();
    }

    public function __wakeup()
    {
        $this->setup_logger();
        $this->datacenter->__construct($this->settings['connection'], $this->settings['connection_settings']);
        $this->reset_session();
    }

    public function setup_logger()
    {
        if (!\danog\MadelineProto\Logger::$constructed) {
            // Set up logger class
            \danog\MadelineProto\Logger::constructor($this->settings['logger']['logger'], $this->settings['logger']['logger_param']);
        }
    }

    public function reset_session()
    {
        foreach ($this->datacenter->sockets as $id => &$socket) {
            \danog\MadelineProto\Logger::log('Resetting session id and seq_no in DC '.$id.'...');
            $socket->session_id = \phpseclib\Crypt\Random::string(8);
            $socket->seq_no = 0;
        }
    }

    // Switches to a new datacenter and if necessary creates authorization keys, binds them and writes client info
    public function switch_dc($new_dc, $allow_nearest_dc_switch = false)
    {
        \danog\MadelineProto\Logger::log('Switching to DC '.$new_dc.'...');
        $old_dc = $this->datacenter->curdc;
        if (!isset($this->datacenter->sockets[$new_dc])) {
            $this->datacenter->dc_connect($new_dc);
            $this->init_authorization();
            $this->config = $this->write_client_info('help.getConfig');
            $this->parse_config();
            $this->get_nearest_dc($allow_nearest_dc_switch);
        }
        if (
            (isset($this->datacenter->sockets[$old_dc]->authorized) && $this->datacenter->sockets[$old_dc]->authorized) &&
            !(isset($this->datacenter->sockets[$new_dc]->authorized) && $this->datacenter->sockets[$new_dc]->authorized && $this->datacenter->sockets[$new_dc]->authorization['user']['id'] === $this->datacenter->sockets[$old_dc]->authorization['user']['id'])
        ) {
            $this->datacenter->curdc = $old_dc;
            $exported_authorization = $this->method_call('auth.exportAuthorization', ['dc_id' => $new_dc]);
            $this->datacenter->curdc = $new_dc;
            if (isset($this->datacenter->sockets[$new_dc]->authorized) && $this->datacenter->sockets[$new_dc]->authorized && $this->datacenter->sockets[$new_dc]->authorization['user']['id'] !== $this->datacenter->sockets[$old_dc]->authorization['user']['id']) {
                $this->method_call('auth.logOut');
            }
            $this->datacenter->authorization = $this->method_call('auth.importAuthorization', $exported_authorization);
            $this->datacenter->authorized = true;
        }
    }

    // Creates authorization keys
    public function init_authorization()
    {
        if ($this->datacenter->session_id == null) {
            $this->datacenter->session_id = \phpseclib\Crypt\Random::string(8);
        }
        if ($this->datacenter->temp_auth_key == null || $this->datacenter->auth_key == null) {
            if ($this->datacenter->auth_key == null) {
                \danog\MadelineProto\Logger::log('Generating permanent authorization key...');
                $this->datacenter->auth_key = $this->create_auth_key(-1);
            }
            \danog\MadelineProto\Logger::log('Generating temporary authorization key...');
            $this->datacenter->temp_auth_key = $this->create_auth_key($this->settings['authorization']['default_temp_auth_key_expires_in']);
            $this->bind_temp_auth_key($this->settings['authorization']['default_temp_auth_key_expires_in']);
        }
    }

    public function write_client_info($method, $arguments = [])
    {
        \danog\MadelineProto\Logger::log('Writing client info (also executing '.$method.')...');

        return $this->method_call(
            'invokeWithLayer',
            [
                'layer' => $this->settings['tl_schema']['layer'],
                'query' => $this->tl->serialize_method('initConnection',
                    array_merge(
                        $this->settings['app_info'],
                        ['query' => $this->tl->serialize_method($method, $arguments)]
                    )
                ),
            ]
        );
    }

    public function get_nearest_dc($allow_switch)
    {
        $nearest_dc = $this->method_call('help.getNearestDc');
        \danog\MadelineProto\Logger::log("We're in ".$nearest_dc['country'].', current dc is '.$nearest_dc['this_dc'].', nearest dc is '.$nearest_dc['nearest_dc'].'.');

        if ($nearest_dc['nearest_dc'] != $nearest_dc['this_dc'] && $allow_switch) {
            $this->switch_dc($nearest_dc['nearest_dc']);
            $this->settings['connection_settings']['default_dc'] = $nearest_dc['nearest_dc'];
        }
    }

    public function get_config()
    {
        if ($this->config['expires'] > time()) {
            return;
        }
        $this->config = $this->method_call('help.getConfig');
        $this->parse_config();
    }

    public function parse_config()
    {
        \danog\MadelineProto\Logger::log('Received config!', $this->config);
        foreach ($this->config['dc_options'] as $dc) {
            $test = $this->config['test_mode'] ? 'test' : 'main';
            $ipv6 = ($dc['ipv6'] ? 'ipv6' : 'ipv4');
            $id = $dc['id'];
            $test .= (isset($this->settings['connection'][$test][$ipv6][$id]) && $this->settings['connection'][$test][$ipv6][$id]['ip_address'] != $dc['ip_address']) ? '_bk' : '';
            $this->settings['connection'][$test][$ipv6][$id] = $dc;
        }
        unset($this->config['dc_options']);
    }
}
