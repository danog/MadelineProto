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
                'auth_key'                         => null,
                'temp_auth_key'                    => null,
                'default_temp_auth_key_expires_in' => 86400,
                'session_id'                       => \phpseclib\Crypt\Random::string(8),
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
                    'test_mode' => true,
                    'port'      => '443',
                ],
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
                'layer'         => 55,
                'src'           => [
                    __DIR__.'/TL_mtproto_v1.json',
                    __DIR__.'/TL_telegram_v55.json',
                ],
            ],
            'logging'       => [
                'logging'       => 1,
                'logging_param' => '/tmp/MadelineProto.log',
                'logging'       => 3,
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
        // Istantiate logging class
        $this->log = new Logging($this->settings['logging']['logging'], $this->settings['logging']['logging_param']);

        // Connect to servers
        $this->log->log('Connecting to server...');
        $this->connection = new DataCenter($this->settings['connection'], $this->settings['connection_settings']);
        $this->connection->dc_connect(2);

        // Load rsa key
        $this->log->log('Loading RSA key...');
        $this->key = new RSA($settings['authorization']['rsa_key']);

        // Istantiate struct class
        $this->log->log('Initializing StructTools...');
        $this->struct = new \danog\PHP\StructTools();

        // Istantiate TL class
        $this->log->log('Translating tl schemas...');
        $this->tl = new TL\TL($this->settings['tl_schema']['src']);

        $this->seq_no = 0;
        $this->timedelta = 0; // time delta
        $this->incoming_messages = [];
        $this->outgoing_messages = [];
        $this->future_salts = [];

        if ($this->settings['authorization']['temp_auth_key'] == null || $this->settings['authorization']['auth_key'] == null) {
            if ($this->settings['authorization']['auth_key'] == null) {
                $this->log->log('Generating permanent authorization key...');
                $this->settings['authorization']['auth_key'] = $this->create_auth_key(-1);
            }
            $this->log->log('Generating temporary authorization key...');
            $this->settings['authorization']['temp_auth_key'] = $this->create_auth_key($this->settings['authorization']['default_temp_auth_key_expires_in']);
        }
        $this->write_client_info();
        $this->bind_temp_auth_key($this->settings['authorization']['default_temp_auth_key_expires_in']);
        $nearestDc = $this->method_call('auth.sendCode', [
            'phone_number' => '393373737',
            'sms_type' => 5,
            'api_id' => $this->settings['app_info']['api_id'],
            'api_hash' => $this->settings['app_info']['api_hash'],
            'lang_code' => $this->settings['app_info']['lang_code'],
        ]);
var_dump($nearestDc);
    }

    public function write_client_info() {
        $this->log->log('Writing client info...');
        $nearestDc = $this->method_call('invokeWithLayer', [
            'layer' => $this->settings['tl_schema']['layer'],
            'query' => $this->tl->serialize_method('initConnection',
                array_merge(
                    $this->settings['app_info'],
                    ['query' => $this->tl->serialize_method('help.getNearestDc', [])]
                )
             ),
        ]);
        $this->log->log('Current dc is '.$nearestDc['this_dc'].', nearest dc is '.$nearestDc['nearest_dc'].' in '.$nearestDc['country'].'.');

        if ($nearestDc['nearest_dc'] != $nearestDc['this_dc']) {
            $this->log->log('Switching to dc '.$nearestDc['nearest_dc'].'...');
            $this->connection->dc_connect($nearestDc['nearest_dc']);
        }
    }
    public function __destruct()
    {
        unset($this->sock);
    }
}
