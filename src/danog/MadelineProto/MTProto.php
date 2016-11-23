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

    public function __construct($settings = [])
    {
        // Set default settings
        $default_settings = [
            'authorization' => [
                'default_temp_auth_key_expires_in' => 86400,
                'rsa_key'                          => '-----BEGIN RSA PUBLIC KEY-----
MIIBCgKCAQEAwVACPi9w23mF3tBkdZz+zwrzKOaaQdr01vAbU4E1pvkfj4sqDsm6
lyDONS789sVoD/xCS9Y0hkkC3gtL1tSfTlgCMOOul9lcixlEKzwKENj1Yz/s7daS
an9tqw3bfUV/nqgbhGX81v/+7RFAEd+RwFnK7a+XYl9sluzHRyVVaTTveB2GazTw
Efzk2DWgkBluml8OREmvfraX3bkHZJTKX4EQSjBbbdJ2ZXIsRrYOXfaA+xayEGB+
8hdlLmAjbCVfaigxX0CDqWeR1yFL9kwd9P0NsZRPsmoqVwMbMu7mStFai6aIhc3n
Slv8kg9qv1m6XHVQY3PnEw+QQtqSIXklHwIDAQAB
-----END RSA PUBLIC KEY-----',
            ],
            'connection' => [
                'ssl_subdomains' => [
                    1 => 'pluto',
                    2 => 'venus',
                    3 => 'aurora',
                    4 => 'vesta',
                    5 => 'flora',
                ],
                'test' => [
                    1 => '149.154.175.10',
                    2 => '149.154.167.40',
                    3 => '149.154.175.117',
                ],
                'main' => [
                    1 => '149.154.175.50',
                    2 => '149.154.167.51',
                    3 => '149.154.175.100',
                    4 => '149.154.167.91',
                    5 => '149.154.171.5',
                ],
            ],
            'connection_settings' => [
                'all' => [
                    'protocol'  => 'tcp_full',
                    'test_mode' => false,
                    'port'      => '443',
                ],
                'default_dc' => 4,
            ],
            'app_info' => [
                'api_id'          => 25628,
                'api_hash'        => '1fe17cda7d355166cdaa71f04122873c',
                'device_model'    => php_uname('s'),
                'system_version'  => php_uname('r'),
                'app_version'     => 'Unicorn',
                'lang_code'       => 'en',
            ],
            'tl_schema'     => [
                'layer'         => 57,
                'src'           => [
                    'mtproto'  => __DIR__.'/TL_mtproto_v1.json',
                    'telegram' => __DIR__.'/TL_telegram_v57.json',
                ],
            ],
            'logger'       => [
                'logger'       => 1,
                'logger_param' => '/tmp/MadelineProto.log',
                'logger'       => 3,
            ],
            'max_tries'         => [
                'query'         => 5,
                'authorization' => 5,
                'response'      => 5,
            ],
            'msg_array_limit'        => [
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

        $this->incoming_messages = [];
        $this->outgoing_messages = [];
        $this->future_salts = [];

        $this->switch_dc($this->settings['connection_settings']['default_dc'], true);
    }

    public function setup_logger()
    {
        if (!\danog\MadelineProto\Logger::$constructed) {
            // Set up logger class
            \danog\MadelineProto\Logger::constructor($this->settings['logger']['logger'], $this->settings['logger']['logger_param']);
        }
    }

    // Switches to a new datacenter and if necessary creates authorization keys, binds them and writes client info
    public function switch_dc($new_dc, $allow_nearest_dc_switch = false)
    {
        \danog\MadelineProto\Logger::log('Switching to DC '.$new_dc.'...');
        if ($this->datacenter->dc_connect($new_dc)) {
            $this->init_authorization();
            $this->bind_temp_auth_key($this->settings['authorization']['default_temp_auth_key_expires_in']);
            $this->write_client_info($allow_nearest_dc_switch);
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
        }
    }

    public function write_client_info($allow_switch)
    {
        \danog\MadelineProto\Logger::log('Writing client info...');
        $nearest_dc = $this->method_call(
            'invokeWithLayer',
            [
                'layer' => $this->settings['tl_schema']['layer'],
                'query' => $this->tl->serialize_method('initConnection',
                    array_merge(
                        $this->settings['app_info'],
                        ['query' => $this->tl->serialize_method('help.getNearestDc', [])]
                    )
                ),
            ]
        );
        \danog\MadelineProto\Logger::log("We're in ".$nearest_dc['country'].', current dc is '.$nearest_dc['this_dc'].', nearest dc is '.$nearest_dc['nearest_dc'].'.');

        if ($nearest_dc['nearest_dc'] != $nearest_dc['this_dc'] && $allow_switch) {
            $this->switch_dc($nearest_dc['nearest_dc']);
            $this->settings['connection_settings']['default_dc'] = $nearest_dc['nearest_dc'];
        }
    }
}
