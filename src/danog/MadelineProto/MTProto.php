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
 * Manages all of the mtproto stuff.
 */
class MTProto
{
    use \danog\MadelineProto\MTProtoTools\AckHandler;
    use \danog\MadelineProto\MTProtoTools\AuthKeyHandler;
    use \danog\MadelineProto\MTProtoTools\CallHandler;
    use \danog\MadelineProto\MTProtoTools\Crypt;
    use \danog\MadelineProto\MTProtoTools\MessageHandler;
    use \danog\MadelineProto\MTProtoTools\MsgIdHandler;
    use \danog\MadelineProto\MTProtoTools\PeerHandler;
    use \danog\MadelineProto\MTProtoTools\ResponseHandler;
    //use \danog\MadelineProto\MTProtoTools\SaltHandler;
    use \danog\MadelineProto\MTProtoTools\SeqNoHandler;
    use \danog\MadelineProto\MTProtoTools\UpdateHandler;
    use \danog\MadelineProto\MTProtoTools\Files;
    use \danog\MadelineProto\SecretChats\AuthKeyHandler;
    use \danog\MadelineProto\SecretChats\MessageHandler;
    use \danog\MadelineProto\SecretChats\ResponseHandler;
    use \danog\MadelineProto\SecretChats\SeqNoHandler;
    use \danog\MadelineProto\TL\TL;
    use \danog\MadelineProto\TL\Conversion\BotAPI;
    use \danog\MadelineProto\TL\Conversion\BotAPIFiles;
    use \danog\MadelineProto\TL\Conversion\Extension;
    use \danog\MadelineProto\TL\Conversion\TD;
    use \danog\MadelineProto\Tools;
    use \danog\MadelineProto\VoIP\AuthKeyHandler;

    public $settings = [];
    public $config = ['expires' => -1];
    public $ipv6 = false;
    public $should_serialize = true;
    public $authorization = null;
    public $authorized = false;
    public $login_temp_status = 'none';
    public $bigint = false;
    public $run_workers = false;
    public $threads = false;

    public function __construct($settings = [])
    {
        //if ($this->unserialized($settings)) return true;
        $this->bigint = PHP_INT_SIZE < 8;
        // Parse settings
        $this->parse_settings($settings);

        // Connect to servers
        \danog\MadelineProto\Logger::log(['Istantiating DataCenter...'], Logger::ULTRA_VERBOSE);
        if (isset($this->datacenter)) {
            $this->datacenter->__construct($this->settings['connection'], $this->settings['connection_settings']);
        } else {
            $this->datacenter = new DataCenter($this->settings['connection'], $this->settings['connection_settings']);
        }
        // Load rsa key
        \danog\MadelineProto\Logger::log(['Loading RSA key...'], Logger::ULTRA_VERBOSE);
        $key = new RSA($this->settings['authorization']['rsa_key']);
        $this->rsa_keys[$key->fp] = $key;

        // Istantiate TL class
        \danog\MadelineProto\Logger::log(['Translating tl schemas...'], Logger::ULTRA_VERBOSE);
        $this->construct_TL($this->settings['tl_schema']['src']);
        /*
         * ***********************************************************************
         * Define some needed numbers for BigInteger
         */
        \danog\MadelineProto\Logger::log(['Executing dh_prime checks (0/3)...'], \danog\MadelineProto\Logger::ULTRA_VERBOSE);

        $this->zero = new \phpseclib\Math\BigInteger(0);
        $this->one = new \phpseclib\Math\BigInteger(1);
        $this->two = new \phpseclib\Math\BigInteger(2);
        $this->three = new \phpseclib\Math\BigInteger(3);
        $this->four = new \phpseclib\Math\BigInteger(4);
        $this->twoe1984 = new \phpseclib\Math\BigInteger('1751908409537131537220509645351687597690304110853111572994449976845956819751541616602568796259317428464425605223064365804210081422215355425149431390635151955247955156636234741221447435733643262808668929902091770092492911737768377135426590363166295684370498604708288556044687341394398676292971255828404734517580702346564613427770683056761383955397564338690628093211465848244049196353703022640400205739093118270803778352768276670202698397214556629204420309965547056893233608758387329699097930255380715679250799950923553703740673620901978370802540218870279314810722790539899334271514365444369275682816');
        $this->twoe2047 = new \phpseclib\Math\BigInteger('16158503035655503650357438344334975980222051334857742016065172713762327569433945446598600705761456731844358980460949009747059779575245460547544076193224141560315438683650498045875098875194826053398028819192033784138396109321309878080919047169238085235290822926018152521443787945770532904303776199561965192760957166694834171210342487393282284747428088017663161029038902829665513096354230157075129296432088558362971801859230928678799175576150822952201848806616643615613562842355410104862578550863465661734839271290328348967522998634176499319107762583194718667771801067716614802322659239302476074096777926805529798115328');
        $this->twoe2048 = new \phpseclib\Math\BigInteger('32317006071311007300714876688669951960444102669715484032130345427524655138867890893197201411522913463688717960921898019494119559150490921095088152386448283120630877367300996091750197750389652106796057638384067568276792218642619756161838094338476170470581645852036305042887575891541065808607552399123930385521914333389668342420684974786564569494856176035326322058077805659331026192708460314150258592864177116725943603718461857357598351152301645904403697613233287231227125684710820209725157101726931323469678542580656697935045997268352998638215525166389437335543602135433229604645318478604952148193555853611059596230656');

        $this->setup_threads();

        $this->connect_to_all_dcs();
        $this->datacenter->curdc = 2;

        if (!isset($this->authorization['user']['bot']) || !$this->authorization['user']['bot']) {
            $nearest_dc = $this->method_call('help.getNearestDc', [], ['datacenter' => $this->datacenter->curdc]);
            \danog\MadelineProto\Logger::log(["We're in ".$nearest_dc['country'].', current dc is '.$nearest_dc['this_dc'].', nearest dc is '.$nearest_dc['nearest_dc'].'.'], Logger::NOTICE);

            if ($nearest_dc['nearest_dc'] != $nearest_dc['this_dc']) {
                $this->datacenter->curdc = $nearest_dc['nearest_dc'];
                $this->settings['connection_settings']['default_dc'] = $nearest_dc['nearest_dc'];
                $this->should_serialize = true;
            }
        }
        $this->get_config([], ['datacenter' => $this->datacenter->curdc]);
        $this->v = $this->getV();
        $this->should_serialize = true;
    }

    public function __sleep()
    {
        $t = get_object_vars($this);
        if (isset($t['reader_pool'])) {
            unset($t['reader_pool']);
        }
        if (isset($t['readers'])) {
            unset($t['readers']);
        }

        return array_keys($t);
    }

    public function __wakeup()
    {
        set_error_handler(['\danog\MadelineProto\Exception', 'ExceptionErrorHandler']);
        $this->setup_logger();
        if (class_exists('\Thread') && method_exists('\Thread', 'getCurrentThread') && is_object(\Thread::getCurrentThread())) {
            return;
        }
        /*
        if (method_exists($this->datacenter, 'wakeup')) $this->datacenter = $this->datacenter->wakeup();
        foreach ($this->rsa_keys as $key => $elem) {
            if (method_exists($elem, 'wakeup')) $this->rsa_keys[$key] = $elem->wakeup();
        }
        foreach ($this->datacenter->sockets as $key => $elem) {
            if (method_exists($elem, 'wakeup')) $this->datacenter->sockets[$key] = $elem->wakeup();
        }
        */
        $this->getting_state = false;
        $this->bigint = PHP_INT_SIZE < 8;
        $this->reset_session();
        if (!isset($this->v) || $this->v !== $this->getV()) {
            \danog\MadelineProto\Logger::log(['Serialization is out of date, reconstructing object!'], Logger::WARNING);
            $settings = $this->settings;
            if (isset($settings['updates']['callback'][0]) && $settings['updates']['callback'][0] === $this) {
                $settings['updates']['callback'] = 'get_updates_update_handler';
            }
            unset($settings['tl_schema']);
            $this->reset_session(true, true);
            $this->__construct($settings);
        }
        $this->datacenter->__construct($this->settings['connection'], $this->settings['connection_settings']);
        $this->setup_threads();
        if ($this->authorized && $this->settings['updates']['handle_updates']) {
            \danog\MadelineProto\Logger::log(['Getting updates after deserialization...'], Logger::NOTICE);
            $this->get_updates_difference();
        }
    }

    public function __destruct()
    {
        if (isset($this->reader_pool)) {
            $this->run_workers = false;
            while ($number = $this->reader_pool->collect()) {
                \danog\MadelineProto\Logger::log(['Shutting down reader pool, '.$number.' jobs left'], \danog\MadelineProto\Logger::NOTICE);
            }
            $this->reader_pool->shutdown();
        }
    }

    public function setup_threads()
    {
        if ($this->threads = $this->run_workers = class_exists('\Pool') && in_array(php_sapi_name(), ['cli', 'phpdbg']) && $this->settings['threading']['allow_threading'] && extension_loaded('pthreads')) {
            \danog\MadelineProto\Logger::log(['THREADING IS ENABLED'], \danog\MadelineProto\Logger::NOTICE);
            $this->start_threads();
        }
    }

    public function start_threads()
    {
        if ($this->threads) {
            $dcs = $this->datacenter->get_dcs();
            if (!isset($this->reader_pool)) {
                $this->reader_pool = new \Pool(count($dcs));
            }
            if (!isset($this->readers)) {
                $this->readers = [];
            }
            foreach ($dcs as $dc) {
                if (!isset($this->readers[$dc])) {
                    $this->readers[$dc] = new \danog\MadelineProto\Threads\SocketReader($this, $dc);
                }
                if (!$this->readers[$dc]->isRunning()) {
                    $this->readers[$dc]->garbage = false;
                    $this->reader_pool->submit($this->readers[$dc]);
                    var_dump('hey');
                    Logger::log(['Socket reader on DC '.$dc.': RESTARTED'], Logger::WARNING);
                    while (!$this->readers[$dc]->ready);
                } else {
                    Logger::log(['Socket reader on DC '.$dc.': WORKING'], Logger::NOTICE);
                }
            }
        }
    }

    public function parse_settings($settings)
    {
        // Detect ipv6
        $google = '';
        try {
            $google = @file_get_contents('http://ipv6.test-ipv6.com/', false, stream_context_create(['http' => ['timeout' => 1]]));
        } catch (Exception $e) {
        }
        $this->ipv6 = strlen($google) > 0;

        // Detect device model
        try {
            $device_model = php_uname('s');
        } catch (Exception $e) {
            $device_model = 'Web server';
        }

        // Detect system version
        try {
            $system_version = php_uname('r');
        } catch (Exception $e) {
            $system_version = phpversion();
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
                    'protocol'     => 'tcp_full', // can be tcp_full, tcp_abridged, tcp_intermediate, http, https, udp (unsupported)
                    'test_mode'    => false, // decides whether to connect to the main telegram servers or to the testing servers (deep telegram)
                    'ipv6'         => $this->ipv6, // decides whether to use ipv6, ipv6 attribute of API attribute of API class contains autodetected boolean
                    'timeout'      => 2, // timeout for sockets
                ],
            ],
            'app_info' => [ // obtained in https://my.telegram.org
                //'api_id'          => 65536,
                //'api_hash'        => '4251a2777e179232705e2462706f4143',
                'device_model'    => $device_model,
                'system_version'  => $system_version,
                'app_version'     => 'Unicorn', // 🌚
//                'app_version'     => $this->getV(),
                'lang_code'       => 'en',
            ],
            'tl_schema'     => [ // TL scheme files
                'layer'         => 66, // layer version
                'src'           => [
                    'mtproto'      => __DIR__.'/TL_mtproto_v1.json', // mtproto TL scheme
                    'telegram'     => __DIR__.'/TL_telegram_v66.tl', // telegram TL scheme
                    'secret'       => __DIR__.'/TL_secret.tl', // secret chats TL scheme
                    'calls'        => __DIR__.'/TL_calls.tl', // calls TL scheme
                    'td'           => __DIR__.'/TL_td.tl', // telegram-cli TL scheme
                    'botAPI'       => __DIR__.'/TL_botAPI.tl', // bot API TL scheme for file ids
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
                'logger'             => 1, // write to
                'logger_param'       => '/tmp/MadelineProto.log',
                'logger'             => 3, // overwrite previous setting and echo logs
                'logger_level'       => Logger::VERBOSE, // Logging level, available logging levels are: ULTRA_VERBOSE, VERBOSE, NOTICE, WARNING, ERROR, FATAL_ERROR. Can be provided as last parameter to the logging function.
                'rollbar_token'      => 'f9fff6689aea4905b58eec73f66c791d',
            ],
            'max_tries'         => [
                'query'         => 5, // How many times should I try to call a method or send an object before throwing an exception
                'authorization' => 5, // How many times should I try to generate an authorization key before throwing an exception
                'response'      => 5, // How many times should I try to get a response of a query before throwing an exception
            ],
            'flood_timeout'     => [
                'wait_if_lt'    => 20, // Sleeps if flood block time is lower than this
            ],
            'msg_array_limit'        => [ // How big should be the arrays containing the incoming and outgoing messages?
                'incoming' => 200,
                'outgoing' => 200,
            ],
            'peer'      => ['full_info_cache_time' => 60],
            'updates'   => [
                'handle_updates'      => true, // Should I handle updates?
                'callback'            => 'get_updates_update_handler', // A callable function that will be called every time an update is received, must accept an array (for the update) as the only parameter
            ],
            'secret_chats' => [
                'accept_chats'      => true, // Should I accept secret chats? Can be true, false or on array of user ids from which to accept chats
            ],
            'calls' => [
                'accept_calls'      => true, // Should I accept calls? Can be true, false or on array of user ids from which to accept calls
                'allow_p2p'         => false, // Should I accept p2p calls?
            ],
            'threading' => [
                'allow_threading' => false, // Should I use threading, if it is enabled?
                'handler_workers' => 10, // How many workers should every message handler pool of each socket reader have
            ],
            'pwr' => [
                'pwr' => false,      // Need info ?
                'db_token' => false, // Need info ?
                'strict' => false,   // Need info ?
                'requests' => true,  // Should I get info about unknown peers from PWRTelegram?
            ],
        ];
        $settings = $this->array_replace_recursive($default_settings, $settings);
        if (!isset($settings['app_info']['api_id'])) {
            throw new Exception('You must provide an api key and an api id, get your own @ my.telegram.org');
        }
        switch ($settings['logger']['logger_level']) {
            case 'ULTRA_VERBOSE': $settings['logger']['logger_level'] = 5; break;
            case 'VERBOSE': $settings['logger']['logger_level'] = 4; break;
            case 'NOTICE': $settings['logger']['logger_level'] = 3; break;
            case 'WARNING': $settings['logger']['logger_level'] = 2; break;
            case 'ERROR': $settings['logger']['logger_level'] = 1; break;
            case 'FATAL ERROR': $settings['logger']['logger_level'] = 0; break;
        }

        $this->settings = $settings;
        // Setup logger
        $this->setup_logger();
        $this->should_serialize = true;
    }

    public function setup_logger()
    {
        \Rollbar\Rollbar::init(['environment' => 'production', 'root' => __DIR__, 'access_token' => isset($this->settings['logger']['rollbar_token']) ? $this->settings['logger']['rollbar_token'] : 'f9fff6689aea4905b58eec73f66c791d'], false, false);
        \danog\MadelineProto\Logger::constructor($this->settings['logger']['logger'], $this->settings['logger']['logger_param'], isset($this->authorization['user']) ? (isset($this->authorization['user']['username']) ? $this->authorization['user']['username'] : $this->authorization['user']['id']) : '', isset($this->settings['logger']['logger_level']) ? $this->settings['logger']['logger_level'] : Logger::VERBOSE);
    }

    public function reset_session($de = true, $auth_key = false)
    {
        foreach ($this->datacenter->sockets as $id => $socket) {
            if ($de) {
                \danog\MadelineProto\Logger::log(['Resetting session id'.($auth_key ? ', authorization key' : '').' and seq_no in DC '.$id.'...'], Logger::VERBOSE);
                $socket->session_id = $this->random(8);
                $socket->session_in_seq_no = 0;
                $socket->session_out_seq_no = 0;
            }
            if ($auth_key) {
                $socket->temp_auth_key = null;
            }
            $socket->incoming_messages = [];
            $socket->outgoing_messages = [];
            $socket->new_outgoing = [];
            $socket->new_incoming = [];
            $this->should_serialize = true;
        }
    }

    // Connects to all datacenters and if necessary creates authorization keys, binds them and writes client info
    public function connect_to_all_dcs()
    {
        foreach ($old = $this->datacenter->get_dcs() as $new_dc) {
            if (!isset($this->datacenter->sockets[$new_dc])) {
                $this->datacenter->dc_connect($new_dc);
            }
        }
        $this->init_authorization();
        if ($old !== $this->datacenter->get_dcs()) {
            $this->connect_to_all_dcs();
        }
    }

    private $initing_authorization = false;

    // Creates authorization keys
    public function init_authorization()
    {
        $this->initing_authorization = true;
        foreach ($this->datacenter->sockets as $id => $socket) {
            if (strpos($id, 'media')) {
                continue;
            }
            if ($socket->session_id === null) {
                $socket->session_id = $this->random(8);
                $socket->session_in_seq_no = 0;
                $socket->session_out_seq_no = 0;
                $this->should_serialize = true;
            }
            if ($socket->temp_auth_key === null || $socket->auth_key === null) {
                if ($socket->auth_key === null) {
                    \danog\MadelineProto\Logger::log(['Generating permanent authorization key for DC '.$id.'...'], Logger::NOTICE);
                    $socket->auth_key = $this->create_auth_key(-1, $id);
                }
                \danog\MadelineProto\Logger::log(['Generating temporary authorization key for DC '.$id.'...'], Logger::NOTICE);
                $socket->temp_auth_key = $this->create_auth_key($this->settings['authorization']['default_temp_auth_key_expires_in'], $id);
                $this->bind_temp_auth_key($this->settings['authorization']['default_temp_auth_key_expires_in'], $id);
                $this->get_config($this->write_client_info('help.getConfig', [], ['datacenter' => $id]));
                if (in_array($socket->protocol, ['http', 'https'])) {
                    $this->method_call('http_wait', ['max_wait' => 0, 'wait_after' => 0, 'max_delay' => 0], ['datacenter' => $id]);
                }
                $this->should_serialize = true;
            }
        }
        $this->initing_authorization = true;
    }

    public function sync_authorization($authorized_dc)
    {
        $this->getting_state = true;
        foreach ($this->datacenter->sockets as $new_dc => $socket) {
            if (($int_dc = preg_replace('|/D+|', '', $new_dc)) == $authorized_dc) {
                continue;
            }
            if ($int_dc != $new_dc) {
                continue;
            }
            if (preg_match('|media|', $new_dc)) {
                continue;
            }
            \danog\MadelineProto\Logger::log(['Copying authorization from dc '.$authorized_dc.' to dc '.$new_dc.'...'], Logger::VERBOSE);
            $this->should_serialize = true;
            $exported_authorization = $this->method_call('auth.exportAuthorization', ['dc_id' => $new_dc], ['datacenter' => $authorized_dc]);
            $this->method_call('auth.logOut', [], ['datacenter' => $new_dc]);
            $this->method_call('auth.importAuthorization', $exported_authorization, ['datacenter' => $new_dc]);
        }
        $this->getting_state = false;
    }

    public function write_client_info($method, $arguments = [], $options = [])
    {
        \danog\MadelineProto\Logger::log(['Writing client info (also executing '.$method.')...'], Logger::NOTICE);

        return $this->method_call(
            'invokeWithLayer',
            [
                'layer' => $this->settings['tl_schema']['layer'],
                'query' => $this->serialize_method('initConnection',
                    [
                        'api_id'         => $this->settings['app_info']['api_id'],
                        'api_hash'       => $this->settings['app_info']['api_hash'],
                        'device_model'   => strpos($options['datacenter'], 'cdn') === false ? $this->settings['app_info']['device_model'] : 'n/a',
                        'system_version' => strpos($options['datacenter'], 'cdn') === false ? $this->settings['app_info']['system_version'] : 'n/a',
                        'app_version'    => $this->settings['app_info']['app_version'],
                        'lang_code'      => $this->settings['app_info']['lang_code'],
                        'query'          => $this->serialize_method($method, $arguments),
                    ]
                ),
            ],
            $options
        );
    }

    public function get_config($config = [], $options = [])
    {
        if ($this->config['expires'] > time()) {
            return;
        }
        $this->config = empty($config) ? $this->method_call('help.getConfig', $config, $options) : $config;
        $this->should_serialize = true;
        $this->parse_config();
    }

    public function parse_config()
    {
        $this->parse_dc_options($this->config['dc_options']);
        unset($this->config['dc_options']);
        \danog\MadelineProto\Logger::log(['Updated config!', $this->config], Logger::NOTICE);
    }

    public function parse_dc_options($dc_options)
    {
        foreach ($dc_options as $dc) {
            $test = $this->config['test_mode'] ? 'test' : 'main';
            $id = $dc['id'];
            $id .= $dc['cdn'] ? '_cdn' : '';
            $id .= $dc['media_only'] ? '_media' : '';
            $ipv6 = ($dc['ipv6'] ? 'ipv6' : 'ipv4');
            $id .= (isset($this->settings['connection'][$test][$ipv6][$id]) && $this->settings['connection'][$test][$ipv6][$id]['ip_address'] != $dc['ip_address']) ? '_bk' : '';
            $this->settings['connection'][$test][$ipv6][$id] = $dc;
        }
        $this->datacenter->__construct($this->settings['connection'], $this->settings['connection_settings']);
        $this->should_serialize = true;
    }

    public function getV()
    {
        return 24;
    }

    public function get_self()
    {
        return $this->authorization['user'];
    }
}
