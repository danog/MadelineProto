<?php

/*
Copyright 2016-2018 Daniil Gentili
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
    use \danog\Serializable;
    use \danog\MadelineProto\MTProtoTools\AckHandler;
    use \danog\MadelineProto\MTProtoTools\AuthKeyHandler;
    use \danog\MadelineProto\MTProtoTools\CallHandler;
    use \danog\MadelineProto\MTProtoTools\Crypt;
    use \danog\MadelineProto\MTProtoTools\MessageHandler;
    use \danog\MadelineProto\MTProtoTools\MsgIdHandler;
    use \danog\MadelineProto\MTProtoTools\PeerHandler;
    use \danog\MadelineProto\MTProtoTools\ResponseHandler;
    use \danog\MadelineProto\MTProtoTools\SaltHandler;
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
    use \danog\MadelineProto\Wrappers\DialogHandler;
    use \danog\MadelineProto\Wrappers\Events;
    use \danog\MadelineProto\Wrappers\Webhook;
    use \danog\MadelineProto\Wrappers\Callback;
    use \danog\MadelineProto\Wrappers\Login;
    use \danog\MadelineProto\Wrappers\Loop;
    use \danog\MadelineProto\Wrappers\Noop;
    use \danog\MadelineProto\Wrappers\Start;
    use \danog\MadelineProto\Wrappers\Templates;
    use \danog\MadelineProto\Wrappers\TOS;

    /*
        const V = 71;
    */
    const V = 104;
    const NOT_LOGGED_IN = 0;
    const WAITING_CODE = 1;
    const WAITING_SIGNUP = -1;
    const WAITING_PASSWORD = 2;
    const LOGGED_IN = 3;
    const DISALLOWED_METHODS = ['messages.receivedQueue' => 'You cannot use this method directly', 'messages.getDhConfig' => 'You cannot use this method directly, instead use $MadelineProto->get_dh_config();', 'auth.bindTempAuthKey' => 'You cannot use this method directly, instead modify the PFS and default_temp_auth_key_expires_in settings, see https://docs.madelineproto.xyz/docs/SETTINGS.html for more info', 'auth.exportAuthorization' => 'You cannot use this method directly, use $MadelineProto->export_authorization() instead, see https://docs.madelineproto.xyz/docs/LOGIN.html', 'auth.importAuthorization' => 'You cannot use this method directly, use $MadelineProto->import_authorization($authorization) instead, see https://docs.madelineproto.xyz/docs/LOGIN.html', 'auth.logOut' => 'You cannot use this method directly, use the logout method instead (see https://docs.madelineproto.xyz for more info)', 'auth.importBotAuthorization' => 'You cannot use this method directly, use the bot_login method instead (see https://docs.madelineproto.xyz for more info)', 'auth.sendCode' => 'You cannot use this method directly, use the phone_login method instead (see https://docs.madelineproto.xyz for more info)', 'auth.signIn' => 'You cannot use this method directly, use the complete_phone_login method instead (see https://docs.madelineproto.xyz for more info)', 'auth.checkPassword' => 'You cannot use this method directly, use the complete_2fa_login method instead (see https://docs.madelineproto.xyz for more info)', 'auth.signUp' => 'You cannot use this method directly, use the complete_signup method instead (see https://docs.madelineproto.xyz for more info)', 'users.getFullUser' => 'You cannot use this method directly, use the get_pwr_chat, get_info, get_full_info methods instead (see https://docs.madelineproto.xyz for more info)', 'channels.getFullChannel' => 'You cannot use this method directly, use the get_pwr_chat, get_info, get_full_info methods instead (see https://docs.madelineproto.xyz for more info)', 'messages.getFullChat' => 'You cannot use this method directly, use the get_pwr_chat, get_info, get_full_info methods instead (see https://docs.madelineproto.xyz for more info)', 'contacts.resolveUsername' => 'You cannot use this method directly, use the resolve_username, get_pwr_chat, get_info, get_full_info methods instead (see https://docs.madelineproto.xyz for more info)', 'messages.acceptEncryption' => 'You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling secret chats', 'messages.discardEncryption' => 'You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling secret chats', 'messages.requestEncryption' => 'You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling secret chats', 'phone.requestCall' => 'You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls', 'phone.acceptCall' => 'You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls', 'phone.confirmCall' => 'You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls', 'phone.discardCall' => 'You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls', 'updates.getChannelDifference' => 'You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling updates', 'updates.getDifference' => 'You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling updates', 'updates.getState' => 'You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling updates', 'upload.getCdnFile' => 'You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info', 'upload.getFileHashes' => 'You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info', 'upload.getCdnFileHashes' => 'You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info', 'upload.reuploadCdnFile' => 'You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info', 'upload.getFile' => 'You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info', 'upload.saveFilePart' => 'You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info', 'upload.saveBigFilePart' => 'You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info'];
    const BAD_MSG_ERROR_CODES = [16 => 'msg_id too low (most likely, client time is wrong; it would be worthwhile to synchronize it using msg_id notifications and re-send the original message with the â€œcorrectâ€ msg_id or wrap it in a container with a new msg_id if the original message had waited too long on the client to be transmitted)', 17 => 'msg_id too high (similar to the previous case, the client time has to be synchronized, and the message re-sent with the correct msg_id)', 18 => 'incorrect two lower order msg_id bits (the server expects client message msg_id to be divisible by 4)', 19 => 'container msg_id is the same as msg_id of a previously received message (this must never happen)', 20 => 'message too old, and it cannot be verified whether the server has received a message with this msg_id or not', 32 => 'msg_seqno too low (the server has already received a message with a lower msg_id but with either a higher or an equal and odd seqno)', 33 => 'msg_seqno too high (similarly, there is a message with a higher msg_id but with either a lower or an equal and odd seqno)', 34 => 'an even msg_seqno expected (irrelevant message), but odd received', 35 => 'odd msg_seqno expected (relevant message), but even received', 48 => 'incorrect server salt (in this case, the bad_server_salt response is received with the correct salt, and the message is to be re-sent with it)', 64 => 'invalid container.'];
    const MSGS_INFO_FLAGS = [1 => 'nothing is known about the message (msg_id too low, the other party may have forgotten it)', 2 => 'message not received (msg_id falls within the range of stored identifiers; however, the other party has certainly not received a message like that)', 3 => 'message not received (msg_id too high; however, the other party has certainly not received it yet)', 4 => 'message received (note that this response is also at the same time a receipt acknowledgment)', 8 => ' and message already acknowledged', 16 => ' and message not requiring acknowledgment', 32 => ' and RPC query contained in message being processed or processing already complete', 64 => ' and content-related response to message already generated', 128 => ' and other party knows for a fact that message is already received'];
    const REQUESTED = 0;
    const ACCEPTED = 1;
    const CONFIRMED = 2;
    const READY = 3;
    const TD_PARAMS_CONVERSION = ['updateNewMessage' => ['_' => 'updateNewMessage', 'disable_notification' => ['message', 'silent'], 'message' => ['message']], 'message' => ['_' => 'message', 'id' => ['id'], 'sender_user_id' => ['from_id'], 'chat_id' => ['to_id', 'choose_chat_id_from_botapi'], 'send_state' => ['choose_incoming_or_sent'], 'can_be_edited' => ['choose_can_edit'], 'can_be_deleted' => ['choose_can_delete'], 'is_post' => ['post'], 'date' => ['date'], 'edit_date' => ['edit_date'], 'forward_info' => ['fwd_info', 'choose_forward_info'], 'reply_to_message_id' => ['reply_to_msg_id'], 'ttl' => ['choose_ttl'], 'ttl_expires_in' => ['choose_ttl_expires_in'], 'via_bot_user_id' => ['via_bot_id'], 'views' => ['views'], 'content' => ['choose_message_content'], 'reply_markup' => ['reply_markup']], 'messages.sendMessage' => ['chat_id' => ['peer'], 'reply_to_message_id' => ['reply_to_msg_id'], 'disable_notification' => ['silent'], 'from_background' => ['background'], 'input_message_content' => ['choose_message_content'], 'reply_markup' => ['reply_markup']]];
    const TD_REVERSE = ['sendMessage' => 'messages.sendMessage'];
    const TD_IGNORE = ['updateMessageID'];
    public $hook_url = false;
    public $settings = [];
    private $config = ['expires' => -1];
    private $tos = ['expires' => 0, 'accepted' => true];
    private $initing_authorization = false;
    public $authorization = null;
    public $authorized = 0;
    public $authorized_dc = -1;
    private $rsa_keys = [];
    private $last_recv = 0;
    private $dh_config = ['version' => 0];
    public $chats = [];
    public $channel_participants = [];
    public $last_stored = 0;
    public $qres = [];
    public $full_chats = [];
    private $msg_ids = [];
    private $v = 0;
    private $dialog_params = ['_' => 'MadelineProto.dialogParams', 'limit' => 0, 'offset_date' => 0, 'offset_id' => 0, 'offset_peer' => ['_' => 'inputPeerEmpty'], 'count' => 0];
    private $ipv6 = false;
    public $run_workers = false;
    public $setdem = false;
    public $storage = [];
    private $postpone_updates = false;
    private $postpone_pwrchat = false;
    private $pending_pwrchat = [];
    private $altervista = false;

    public function __magic_construct($settings = [])
    {
        \danog\MadelineProto\Magic::class_exists();
        // Parse settings
        $this->parse_settings($settings);
        if (!defined('\\phpseclib\\Crypt\\Common\\SymmetricKey::MODE_IGE') || \phpseclib\Crypt\Common\SymmetricKey::MODE_IGE !== 6) {
            throw new Exception(\danog\MadelineProto\Lang::$current_lang['phpseclib_fork']);
        }
        if (!extension_loaded('xml')) {
            throw new Exception(['extension', 'xml']);
        }
        if (!extension_loaded('json')) {
            throw new Exception(['extension', 'json']);
        }
        // Connect to servers
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['inst_dc'], Logger::ULTRA_VERBOSE);
        if (!isset($this->datacenter)) {
            $this->datacenter = new DataCenter($this->settings['connection'], $this->settings['connection_settings']);
        }
        // Load rsa keys
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['load_rsa'], Logger::ULTRA_VERBOSE);
        foreach ($this->settings['authorization']['rsa_keys'] as $key) {
            $key = new RSA($key);
            $this->rsa_keys[$key->fp] = $key;
        }
        /*
         * ***********************************************************************
         * Define some needed numbers for BigInteger
         */
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['TL_translation'], Logger::ULTRA_VERBOSE);
        $this->construct_TL($this->settings['tl_schema']['src']);
        $this->connect_to_all_dcs();
        $this->datacenter->curdc = 2;
        if (!isset($this->authorization['user']['bot']) || !$this->authorization['user']['bot']) {
            try {
                $nearest_dc = $this->method_call('help.getNearestDc', [], ['datacenter' => $this->datacenter->curdc]);
                $this->logger->logger(sprintf(\danog\MadelineProto\Lang::$current_lang['nearest_dc'], $nearest_dc['country'], $nearest_dc['nearest_dc']), Logger::NOTICE);
                if ($nearest_dc['nearest_dc'] != $nearest_dc['this_dc']) {
                    $this->settings['connection_settings']['default_dc'] = $this->datacenter->curdc = (int) $nearest_dc['nearest_dc'];
                }
            } catch (RPCErrorException $e) {
                if ($e->rpc !== 'BOT_METHOD_INVALID') {
                    throw $e;
                }
            }
        }
        $this->get_config([], ['datacenter' => $this->datacenter->curdc]);
        $this->v = self::V;

        return $this->settings;
    }

    public function __sleep()
    {
        return ['event_handler', 'event_handler_instance', 'loop_callback', 'web_template', 'encrypted_layer', 'settings', 'config', 'authorization', 'authorized', 'rsa_keys', 'last_recv', 'dh_config', 'chats', 'last_stored', 'qres', 'pending_updates', 'pending_pwrchat', 'postpone_pwrchat', 'updates_state', 'got_state', 'channels_state', 'updates', 'updates_key', 'full_chats', 'msg_ids', 'dialog_params', 'datacenter', 'v', 'constructors', 'td_constructors', 'methods', 'td_methods', 'td_descriptions', 'temp_requested_secret_chats', 'temp_rekeyed_secret_chats', 'secret_chats', 'hook_url', 'storage', 'authorized_dc', 'tos'];
    }

    public function __wakeup()
    {
        set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
        set_exception_handler(['\\danog\\MadelineProto\\Serialization', 'serialize_all']);
        $this->setup_logger();
        if (\danog\MadelineProto\Magic::$has_thread && is_object(\Thread::getCurrentThread())) {
            return;
        }
        Lang::$current_lang = &Lang::$lang['en'];
        if (isset($this->settings['app_info']['lang_code']) && isset(Lang::$lang[$this->settings['app_info']['lang_code']])) {
            Lang::$current_lang = &Lang::$lang[$this->settings['app_info']['lang_code']];
        }
        if (!defined('\\phpseclib\\Crypt\\AES::MODE_IGE')) {
            throw new Exception(\danog\MadelineProto\Lang::$current_lang['phpseclib_fork']);
        }
        if (!extension_loaded('xml')) {
            throw new Exception(['extension', 'xml']);
        }
        $this->altervista = isset($_SERVER['SERVER_ADMIN']) && strpos($_SERVER['SERVER_ADMIN'], 'altervista.org');

        $this->settings['connection_settings']['all']['ipv6'] = \danog\MadelineProto\Magic::$ipv6;
        /*if (isset($this->settings['pwr']['update_handler']) && $this->settings['pwr']['update_handler'] === $this->settings['updates']['callback']) {
            unset($this->settings['pwr']['update_handler']);
            $this->updates = [];
        }*/
        $keys = array_keys((array) get_object_vars($this));
        if (count($keys) !== count(array_unique($keys))) {
            throw new Bug74586Exception();
        }
        if (isset($this->data)) {
            foreach ($this->data as $k => $v) {
                $this->{$k} = $v;
            }
            unset($this->data);
        }
        if ($this->authorized === true) {
            $this->authorized = self::LOGGED_IN;
        }
        $this->updates_state['sync_loading'] = false;
        foreach ($this->channels_state as $key => $state) {
            $this->channels_state[$key]['sync_loading'] = false;
        }
        $this->postpone_updates = false;
        $this->postpone_pwrchat = false;
        $force = false;
        $this->reset_session();

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
        if (isset($backtrace[2]['function']) && isset($backtrace[2]['class']) && isset($backtrace[2]['args']) && $backtrace[2]['class'] === 'danog\\MadelineProto\\API' && $backtrace[2]['function'] === '__magic_construct') {
            if (count($backtrace[2]['args']) === 2) {
                //$this->logger->logger('Updating settings on wakeup');
                $this->parse_settings(array_replace_recursive($this->settings, $backtrace[2]['args'][1]));
            }
            //$this->wrapper = $backtrace[2]['object'];
        }
        if (!isset($this->v) || $this->v !== self::V) {
            $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['serialization_ofd'], Logger::WARNING);
            foreach ($this->datacenter->sockets as $dc_id => $socket) {
                if ($this->authorized === self::LOGGED_IN && strpos($dc_id, '_') === false && $socket->auth_key !== null && $socket->temp_auth_key !== null) {
                    $socket->authorized = true;
                }
            }
            //$this->authorized === self::LOGGED_IN; }
            $settings = $this->settings;
            if (isset($settings['updates']['callback'][0]) && $settings['updates']['callback'][0] === $this) {
                $settings['updates']['callback'] = 'get_updates_update_handler';
            }
            if (isset($settings['updates']['getdifference_interval']) && $settings['updates']['getdifference_interval'] === -1) {
                unset($settings['updates']['getdifference_interval']);
            }
            unset($settings['tl_schema']);
            if (isset($settings['authorization']['rsa_key'])) {
                unset($settings['authorization']['rsa_key']);
            }
            if (!isset($this->full_chats)) {
                $this->full_chats = [];
            }
            if (!isset($this->secret_chats)) {
                $this->secret_chats = [];
            }

            foreach ($this->full_chats as $id => $full) {
                $this->full_chats[$id] = ['full' => $full['full'], 'last_update' => $full['last_update']];
            }
            foreach ($this->secret_chats as $key => &$chat) {
                if (!is_array($chat)) {
                    unset($this->secret_chats[$key]);
                    continue;
                }
                if ($chat['layer'] >= 73) {
                    $chat['mtproto'] = 2;
                } else {
                    $chat['mtproto'] = 1;
                }
            }
            foreach ($settings['connection_settings'] as $key => &$connection) {
                if (!is_array($connection)) {
                    unset($settings['connection_settings'][$key]);
                    continue;
                }
                if (!isset($connection['proxy'])) {
                    $connection['proxy'] = '\\Socket';
                }
                if (!isset($connection['proxy_extra'])) {
                    $connection['proxy_extra'] = [];
                }
                if (!isset($connection['pfs'])) {
                    $connection['pfs'] = extension_loaded('gmp');
                }
            }
            if (!isset($settings['authorization']['rsa_key'])) {
                unset($settings['authorization']['rsa_key']);
            }
            $this->reset_session(true, true);
            $this->config = ['expires' => -1];
            $this->dh_config = ['version' => 0];
            $this->__construct($settings);
            $force = true;
            foreach ($this->secret_chats as $chat => $data) {
                try {
                    if (isset($this->secret_chats[$chat]) && $this->secret_chats[$chat]['InputEncryptedChat'] !== null) {
                        $this->notify_layer($chat);
                    }
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                }
            }
        }
        if (!$this->settings['updates']['handle_old_updates']) {
            $this->channels_state = [];
            $this->got_state = false;
        }
        $this->connect_to_all_dcs();
        //datacenter->__construct($this->settings['connection'], $this->settings['connection_settings']);
        foreach ($this->calls as $id => $controller) {
            if (!is_object($controller)) {
                unset($this->calls[$id]);
            } elseif ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                $controller->setMadeline($this);
                $controller->discard();
            } else {
                $controller->setMadeline($this);
            }
        }
        if ($this->get_self()) {
            $this->authorized = self::LOGGED_IN;
        }
        if ($this->authorized === self::LOGGED_IN) {
            $this->get_cdn_config($this->datacenter->curdc);
            $this->setup_logger();
        }
        if ($this->authorized === self::LOGGED_IN && !$this->authorization['user']['bot'] && $this->settings['peer']['cache_all_peers_on_startup']) {
            $this->get_dialogs($force);
        }
        if ($this->authorized === self::LOGGED_IN && $this->settings['updates']['handle_updates'] && !$this->updates_state['sync_loading']) {
            $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['getupdates_deserialization'], Logger::NOTICE);
            $this->get_updates_difference();
        }
    }

    public function __destruct()
    {
        if (\danog\MadelineProto\Magic::$has_thread && is_object(\Thread::getCurrentThread())) {
            return;
        }
    }

    public function serialize()
    {
        if ($this->wrapper instanceof \danog\MadelineProto\API && isset($this->wrapper->session) && !is_null($this->wrapper->session)) {
            $this->wrapper->serialize($this->wrapper->session);
        }
    }

    public function parse_settings($settings)
    {
        if (!isset($settings['app_info']['api_id']) || !$settings['app_info']['api_id']) {
            if (isset($this->settings['app_info']['api_id']) && $this->settings['app_info']['api_id']) {
                $settings['app_info']['api_id'] = $this->settings['app_info']['api_id'];
                $settings['app_info']['api_hash'] = $this->settings['app_info']['api_hash'];
            } else {
                throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['api_not_set'], 0, null, 'MadelineProto', 1);
            }
        }
        // Detect device model
        try {
            $device_model = php_uname('s');
        } catch (\danog\MadelineProto\Exception $e) {
            $device_model = 'Web server';
        }
        // Detect system version
        try {
            $system_version = php_uname('r');
        } catch (\danog\MadelineProto\Exception $e) {
            $system_version = phpversion();
        }
        // Detect language
        $lang_code = 'en';
        Lang::$current_lang = &Lang::$lang[$lang_code];
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $lang_code = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        } elseif (isset($_SERVER['LANG'])) {
            $lang_code = explode('_', $_SERVER['LANG'])[0];
        }
        if (isset(Lang::$lang[$lang_code])) {
            Lang::$current_lang = &Lang::$lang[$lang_code];
        }
        $this->altervista = isset($_SERVER['SERVER_ADMIN']) && strpos($_SERVER['SERVER_ADMIN'], 'altervista.org');
        // Set default settings
        $default_settings = ['authorization' => [
            // Authorization settings
            'default_temp_auth_key_expires_in' => 1 * 24 * 60 * 60,
            // validity of temporary keys and the binding of the temporary and permanent keys
            'rsa_keys' => ["-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAwVACPi9w23mF3tBkdZz+zwrzKOaaQdr01vAbU4E1pvkfj4sqDsm6\nlyDONS789sVoD/xCS9Y0hkkC3gtL1tSfTlgCMOOul9lcixlEKzwKENj1Yz/s7daS\nan9tqw3bfUV/nqgbhGX81v/+7RFAEd+RwFnK7a+XYl9sluzHRyVVaTTveB2GazTw\nEfzk2DWgkBluml8OREmvfraX3bkHZJTKX4EQSjBbbdJ2ZXIsRrYOXfaA+xayEGB+\n8hdlLmAjbCVfaigxX0CDqWeR1yFL9kwd9P0NsZRPsmoqVwMbMu7mStFai6aIhc3n\nSlv8kg9qv1m6XHVQY3PnEw+QQtqSIXklHwIDAQAB\n-----END RSA PUBLIC KEY-----", "-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAxq7aeLAqJR20tkQQMfRn+ocfrtMlJsQ2Uksfs7Xcoo77jAid0bRt\nksiVmT2HEIJUlRxfABoPBV8wY9zRTUMaMA654pUX41mhyVN+XoerGxFvrs9dF1Ru\nvCHbI02dM2ppPvyytvvMoefRoL5BTcpAihFgm5xCaakgsJ/tH5oVl74CdhQw8J5L\nxI/K++KJBUyZ26Uba1632cOiq05JBUW0Z2vWIOk4BLysk7+U9z+SxynKiZR3/xdi\nXvFKk01R3BHV+GUKM2RYazpS/P8v7eyKhAbKxOdRcFpHLlVwfjyM1VlDQrEZxsMp\nNTLYXb6Sce1Uov0YtNx5wEowlREH1WOTlwIDAQAB\n-----END RSA PUBLIC KEY-----", "-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAsQZnSWVZNfClk29RcDTJQ76n8zZaiTGuUsi8sUhW8AS4PSbPKDm+\nDyJgdHDWdIF3HBzl7DHeFrILuqTs0vfS7Pa2NW8nUBwiaYQmPtwEa4n7bTmBVGsB\n1700/tz8wQWOLUlL2nMv+BPlDhxq4kmJCyJfgrIrHlX8sGPcPA4Y6Rwo0MSqYn3s\ng1Pu5gOKlaT9HKmE6wn5Sut6IiBjWozrRQ6n5h2RXNtO7O2qCDqjgB2vBxhV7B+z\nhRbLbCmW0tYMDsvPpX5M8fsO05svN+lKtCAuz1leFns8piZpptpSCFn7bWxiA9/f\nx5x17D7pfah3Sy2pA+NDXyzSlGcKdaUmwQIDAQAB\n-----END RSA PUBLIC KEY-----", "-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAwqjFW0pi4reKGbkc9pK83Eunwj/k0G8ZTioMMPbZmW99GivMibwa\nxDM9RDWabEMyUtGoQC2ZcDeLWRK3W8jMP6dnEKAlvLkDLfC4fXYHzFO5KHEqF06i\nqAqBdmI1iBGdQv/OQCBcbXIWCGDY2AsiqLhlGQfPOI7/vvKc188rTriocgUtoTUc\n/n/sIUzkgwTqRyvWYynWARWzQg0I9olLBBC2q5RQJJlnYXZwyTL3y9tdb7zOHkks\nWV9IMQmZmyZh/N7sMbGWQpt4NMchGpPGeJ2e5gHBjDnlIf2p1yZOYeUYrdbwcS0t\nUiggS4UeE8TzIuXFQxw7fzEIlmhIaq3FnwIDAQAB\n-----END RSA PUBLIC KEY-----"],
        ], 'connection' => [
            // List of datacenters/subdomains where to connect
            'ssl_subdomains' => [
                // Subdomains of web.telegram.org for https protocol
                1 => 'pluto',
                2 => 'venus',
                3 => 'aurora',
                4 => 'vesta',
                5 => 'flora',
            ],
            'test' => [
                // Test datacenters
                'ipv4' => [
                    // ipv4 addresses
                    2 => [
                        // The rest will be fetched using help.getConfig
                        'ip_address' => '149.154.167.40',
                        'port'       => 443,
                        'media_only' => false,
                        'tcpo_only'  => false,
                    ],
                ],
                'ipv6' => [
                    // ipv6 addresses
                    2 => [
                        // The rest will be fetched using help.getConfig
                        'ip_address' => '2001:067c:04e8:f002:0000:0000:0000:000e',
                        'port'       => 443,
                        'media_only' => false,
                        'tcpo_only'  => false,
                    ],
                ],
            ],
            'main' => [
                // Main datacenters
                'ipv4' => [
                    // ipv4 addresses
                    2 => [
                        // The rest will be fetched using help.getConfig
                        'ip_address' => '149.154.167.51',
                        'port'       => 443,
                        'media_only' => false,
                        'tcpo_only'  => false,
                    ],
                ],
                'ipv6' => [
                    // ipv6 addresses
                    2 => [
                        // The rest will be fetched using help.getConfig
                        'ip_address' => '2001:067c:04e8:f002:0000:0000:0000:000a',
                        'port'       => 443,
                        'media_only' => false,
                        'tcpo_only'  => false,
                    ],
                ],
            ],
        ], 'connection_settings' => [
            // connection settings
            'all' => [
                // These settings will be applied on every datacenter that hasn't a custom settings subarray...
                'protocol' => 'tcp_abridged',
                // can be tcp_full, tcp_abridged, tcp_intermediate, http, https, obfuscated2, udp (unsupported)
                'test_mode' => false,
                // decides whether to connect to the main telegram servers or to the testing servers (deep telegram)
                'ipv6' => \danog\MadelineProto\Magic::$ipv6,
                // decides whether to use ipv6, ipv6 attribute of API attribute of API class contains autodetected boolean
                'timeout' => 2,
                // timeout for sockets
                'proxy' => $this->altervista ? '\\HttpProxy' : '\\Socket',
                // The proxy class to use
                'proxy_extra' => $this->altervista ? ['address' => 'localhost', 'port' => 80] : [],
                // Extra parameters to pass to the proxy class using setExtra
                'pfs' => extension_loaded('gmp'),
            ],
            'default_dc' => 2,
        ], 'app_info' => [
            // obtained in https://my.telegram.org
            //'api_id'          => you should put an API id in the settings array you provide
            //'api_hash'        => you should put an API hash in the settings array you provide
            'device_model'   => $device_model,
            'system_version' => $system_version,
            'app_version'    => 'Unicorn',
            // ðŸŒš
            //                'app_version'     => self::V,
            'lang_code' => $lang_code,
        ], 'tl_schema' => [
            // TL scheme files
            'layer' => 82,
            // layer version
            'src' => [
                'mtproto' => __DIR__.'/TL_mtproto_v1.json',
                // mtproto TL scheme
                'telegram' => __DIR__.'/TL_telegram_v82.tl',
                // telegram TL scheme
                'secret' => __DIR__.'/TL_secret.tl',
                // secret chats TL scheme
                'calls' => __DIR__.'/TL_calls.tl',
                // calls TL scheme
                //'td'           => __DIR__.'/TL_td.tl', // telegram-cli TL scheme
                'botAPI' => __DIR__.'/TL_botAPI.tl',
            ],
        ], 'logger' => [
            // Logger settings
            /*
             * logger modes:
             * 0 - No logger
             * 1 - Log to the default logger destination
             * 2 - Log to file defined in second parameter
             * 3 - Echo logs
             * 4 - Call callable provided in logger_param. logger_param must accept two parameters: array $message, int $level
             *     $message is an array containing the messages the log, $level, is the logging level
             */
            // write to
            'logger_param' => getcwd().'/MadelineProto.log',
            'logger'       => php_sapi_name() === 'cli' ? 3 : 2,
            // overwrite previous setting and echo logs
            'logger_level' => Logger::VERBOSE,
            'max_size'     => 100 * 1024 * 1024,
            // Logging level, available logging levels are: ULTRA_VERBOSE, VERBOSE, NOTICE, WARNING, ERROR, FATAL_ERROR. Can be provided as last parameter to the logging function.
            'rollbar_token' => '',
        ], 'max_tries' => [
            'query' => 5,
            // How many times should I try to call a method or send an object before throwing an exception
            'authorization' => 5,
            // How many times should I try to generate an authorization key before throwing an exception
            'response' => 5,
        ], 'flood_timeout' => ['wait_if_lt' => 20], 'msg_array_limit' => [
            // How big should be the arrays containing the incoming and outgoing messages?
            'incoming'   => 200,
            'outgoing'   => 200,
            'call_queue' => 200,
        ], 'peer' => [
            'full_info_cache_time' => 3600,
            // Full peer info cache validity
            'full_fetch' => false,
            // Should madeline fetch the full member list of every group it meets?
            'cache_all_peers_on_startup' => false,
        ], 'requests' => ['gzip_encode_if_gt' => 500], 'updates' => [
            'handle_updates' => false,
            // Should I handle updates?
            'handle_old_updates' => true,
            // Should I handle old updates on startup?
            'getdifference_interval' => 10,
            // Getdifference manual polling interval
            'callback' => 'get_updates_update_handler',
        ], 'secret_chats' => ['accept_chats' => true], 'serialization' => ['serialization_interval' => 30], 'threading' => [
            'allow_threading' => false,
            // Should I use threading, if it is enabled?
            'handler_workers' => 10,
        ], 'upload' => [
            'allow_automatic_upload' => true,
        ], 'pwr' => [
            'pwr' => false,
            // Need info ?
            'db_token' => false,
            // Need info ?
            'strict' => false,
            // Need info ?
            'requests' => true,
        ]];
        if (!is_array($settings)) {
            $settings = [];
        }
        $settings = array_replace_recursive($this->array_cast_recursive($default_settings, true), $this->array_cast_recursive($settings, true));
        if (isset(Lang::$lang[$settings['app_info']['lang_code']])) {
            Lang::$current_lang = &Lang::$lang[$settings['app_info']['lang_code']];
        }
        /*if ($settings['app_info']['api_id'] < 20) {
              $settings['connection_settings']['all']['protocol'] = 'obfuscated2';
          }*/
        switch ($settings['logger']['logger_level']) {
            case 'ULTRA_VERBOSE':
                $settings['logger']['logger_level'] = 5;
                break;
            case 'VERBOSE':
                $settings['logger']['logger_level'] = 4;
                break;
            case 'NOTICE':
                $settings['logger']['logger_level'] = 3;
                break;
            case 'WARNING':
                $settings['logger']['logger_level'] = 2;
                break;
            case 'ERROR':
                $settings['logger']['logger_level'] = 1;
                break;
            case 'FATAL ERROR':
                $settings['logger']['logger_level'] = 0;
                break;
        }
        $this->settings = $settings;
        if (!$this->settings['updates']['handle_updates']) {
            $this->updates = [];
        }
        // Setup logger
        $this->setup_logger();
    }

    public function setup_logger()
    {
        if (isset($this->settings['logger']['rollbar_token']) && $this->settings['logger']['rollbar_token'] !== '') {
            @\Rollbar\Rollbar::init(['environment' => 'production', 'root' => __DIR__, 'access_token' => isset($this->settings['logger']['rollbar_token']) && !in_array($this->settings['logger']['rollbar_token'], ['f9fff6689aea4905b58eec73f66c791d', '300afd7ccef346ea84d0c185ae831718', '11a8c2fe4c474328b40a28193f8d63f5', 'beef2d426496462ba34dcaad33d44a14']) || $this->settings['pwr']['pwr'] ? $this->settings['logger']['rollbar_token'] : 'c07d9b2f73c2461297b0beaef6c1662f'], false, false);
        } else {
            Exception::$rollbar = false;
            RPCErrorException::$rollbar = false;
        }
        $this->logger = new \danog\MadelineProto\Logger($this->settings['logger']['logger'], isset($this->settings['logger']['logger_param']) ? $this->settings['logger']['logger_param'] : '', isset($this->authorization['user']) ? isset($this->authorization['user']['username']) ? $this->authorization['user']['username'] : $this->authorization['user']['id'] : '', isset($this->settings['logger']['logger_level']) ? $this->settings['logger']['logger_level'] : Logger::VERBOSE, isset($this->settings['logger']['max_size']) ? $this->settings['logger']['max_size'] : 100 * 1024 * 1024);
        if (!\danog\MadelineProto\Logger::$default) {
            \danog\MadelineProto\Logger::constructor($this->settings['logger']['logger'], $this->settings['logger']['logger_param'], isset($this->authorization['user']) ? isset($this->authorization['user']['username']) ? $this->authorization['user']['username'] : $this->authorization['user']['id'] : '', isset($this->settings['logger']['logger_level']) ? $this->settings['logger']['logger_level'] : Logger::VERBOSE, isset($this->settings['logger']['max_size']) ? $this->settings['logger']['max_size'] : 100 * 1024 * 1024);
        }
    }

    public function reset_session($de = true, $auth_key = false)
    {
        if (!is_object($this->datacenter)) {
            throw new Exception(\danog\MadelineProto\Lang::$current_lang['session_corrupted']);
        }
        foreach ($this->datacenter->sockets as $id => $socket) {
            if ($de) {
                //$this->logger->logger(sprintf(\danog\MadelineProto\Lang::$current_lang['reset_session_seqno'], $id), Logger::VERBOSE);
                $socket->session_id = $this->random(8);
                $socket->session_in_seq_no = 0;
                $socket->session_out_seq_no = 0;
                $socket->max_incoming_id = null;
                $socket->max_outgoing_id = null;
            }
            if ($auth_key) {
                $socket->temp_auth_key = null;
            }
            $socket->incoming_messages = [];
            $socket->outgoing_messages = [];
            $socket->new_outgoing = [];
            $socket->new_incoming = [];
        }
    }

    public function is_http($datacenter)
    {
        return in_array($this->datacenter->sockets[$datacenter]->protocol, ['http', 'https', 'https_proxied']);
    }

    public function close_and_reopen($datacenter)
    {
        $this->datacenter->sockets[$datacenter]->close_and_reopen();
        /*if ($this->is_http($datacenter) && $this->datacenter->sockets[$datacenter]->temp_auth_key !== null && isset($this->datacenter->sockets[$datacenter]->temp_auth_key['connection_inited']) && $this->datacenter->sockets[$datacenter]->temp_auth_key['connection_inited'] === true) {
            $this->method_call('ping', ['ping_id' => 0], ['datacenter' => $datacenter]);
        }*/
    }

    // Connects to all datacenters and if necessary creates authorization keys, binds them and writes client info
    public function connect_to_all_dcs()
    {
        $this->datacenter->__construct($this->settings['connection'], $this->settings['connection_settings']);
        foreach ($this->datacenter->get_dcs() as $new_dc) {
            $this->datacenter->dc_connect($new_dc);
        }
        $this->init_authorization();
        foreach ($this->datacenter->get_dcs(false) as $new_dc) {
            $this->datacenter->dc_connect($new_dc);
        }
        $this->init_authorization();
    }

    public function get_config($config = [], $options = [])
    {
        if ($this->config['expires'] > time()) {
            return;
        }
        $this->config = empty($config) ? $this->method_call('help.getConfig', $config, $options) : $config;
        $this->parse_config();
    }

    public function get_cdn_config($datacenter)
    {
        /*
         * ***********************************************************************
         * Fetch RSA keys for CDN datacenters
         */
        try {
            foreach ($this->method_call('help.getCdnConfig', [], ['datacenter' => $datacenter])['public_keys'] as $curkey) {
                $tempkey = new \danog\MadelineProto\RSA($curkey['public_key']);
                $this->rsa_keys[$tempkey->fp] = $tempkey;
            }
        } catch (\danog\MadelineProto\TL\Exception $e) {
            $this->logger->logger($e->getMessage(), \danog\MadelineProto\Logger::FATAL_ERROR);
        }
    }

    public function parse_config()
    {
        if (isset($this->config['dc_options'])) {
            $this->parse_dc_options($this->config['dc_options']);
            unset($this->config['dc_options']);
        }
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['config_updated'], Logger::NOTICE);
        $this->logger->logger($this->config, Logger::NOTICE);
    }

    public function parse_dc_options($dc_options)
    {
        unset($this->settings[$this->config['test_mode']]);
        foreach ($dc_options as $dc) {
            $test = $this->config['test_mode'] ? 'test' : 'main';
            $id = $dc['id'];
            if (isset($dc['static'])) {
                //$id .= $dc['static'] ? '_static' : '';
            }
            if (isset($dc['cdn'])) {
                $id .= $dc['cdn'] ? '_cdn' : '';
            }
            $id .= $dc['media_only'] ? '_media' : '';
            $ipv6 = $dc['ipv6'] ? 'ipv6' : 'ipv4';
            //$id .= isset($this->settings['connection'][$test][$ipv6][$id]) && $this->settings['connection'][$test][$ipv6][$id]['ip_address'] != $dc['ip_address'] ? '_bk' : '';
            if (is_numeric($id)) {
                $id = (int) $id;
            }
            unset($dc['cdn']);
            unset($dc['media_only']);
            unset($dc['id']);
            unset($dc['ipv6']);
            $this->settings['connection'][$test][$ipv6][$id] = $dc;
        }
        $curdc = $this->datacenter->curdc;
        $this->logger->logger('Got new DC options, reconnecting');
        $this->connect_to_all_dcs();
        $this->datacenter->curdc = $curdc;
    }

    public function get_self()
    {
        try {
            $this->authorization = ['user' => $this->method_call('users.getUsers', ['id' => [['_' => 'inputUserSelf']]], ['datacenter' => $this->datacenter->curdc])[0]];
        } catch (RPCErrorException $e) {
            $this->logger->logger($e->getMessage());

            return false;
        }

        return $this->authorization['user'];
    }

    const ALL_MIMES = ['png' => [0 => 'image/png', 1 => 'image/x-png'], 'bmp' => [0 => 'image/bmp', 1 => 'image/x-bmp', 2 => 'image/x-bitmap', 3 => 'image/x-xbitmap', 4 => 'image/x-win-bitmap', 5 => 'image/x-windows-bmp', 6 => 'image/ms-bmp', 7 => 'image/x-ms-bmp', 8 => 'application/bmp', 9 => 'application/x-bmp', 10 => 'application/x-win-bitmap'], 'gif' => [0 => 'image/gif'], 'jpeg' => [0 => 'image/jpeg', 1 => 'image/pjpeg'], 'xspf' => [0 => 'application/xspf+xml'], 'vlc' => [0 => 'application/videolan'], 'wmv' => [0 => 'video/x-ms-wmv', 1 => 'video/x-ms-asf'], 'au' => [0 => 'audio/x-au'], 'ac3' => [0 => 'audio/ac3'], 'flac' => [0 => 'audio/x-flac'], 'ogg' => [0 => 'audio/ogg', 1 => 'video/ogg', 2 => 'application/ogg'], 'kmz' => [0 => 'application/vnd.google-earth.kmz'], 'kml' => [0 => 'application/vnd.google-earth.kml+xml'], 'rtx' => [0 => 'text/richtext'], 'rtf' => [0 => 'text/rtf'], 'jar' => [0 => 'application/java-archive', 1 => 'application/x-java-application', 2 => 'application/x-jar'], 'zip' => [0 => 'application/x-zip', 1 => 'application/zip', 2 => 'application/x-zip-compressed', 3 => 'application/s-compressed', 4 => 'multipart/x-zip'], '7zip' => [0 => 'application/x-compressed'], 'xml' => [0 => 'application/xml', 1 => 'text/xml'], 'svg' => [0 => 'image/svg+xml'], '3g2' => [0 => 'video/3gpp2'], '3gp' => [0 => 'video/3gp', 1 => 'video/3gpp'], 'mp4' => [0 => 'video/mp4'], 'm4a' => [0 => 'audio/x-m4a'], 'f4v' => [0 => 'video/x-f4v'], 'flv' => [0 => 'video/x-flv'], 'webm' => [0 => 'video/webm'], 'aac' => [0 => 'audio/x-acc'], 'm4u' => [0 => 'application/vnd.mpegurl'], 'pdf' => [0 => 'application/pdf', 1 => 'application/octet-stream'], 'pptx' => [0 => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'], 'ppt' => [0 => 'application/powerpoint', 1 => 'application/vnd.ms-powerpoint', 2 => 'application/vnd.ms-office', 3 => 'application/msword'], 'docx' => [0 => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], 'xlsx' => [0 => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 1 => 'application/vnd.ms-excel'], 'xl' => [0 => 'application/excel'], 'xls' => [0 => 'application/msexcel', 1 => 'application/x-msexcel', 2 => 'application/x-ms-excel', 3 => 'application/x-excel', 4 => 'application/x-dos_ms_excel', 5 => 'application/xls', 6 => 'application/x-xls'], 'xsl' => [0 => 'text/xsl'], 'mpeg' => [0 => 'video/mpeg'], 'mov' => [0 => 'video/quicktime'], 'avi' => [0 => 'video/x-msvideo', 1 => 'video/msvideo', 2 => 'video/avi', 3 => 'application/x-troff-msvideo'], 'movie' => [0 => 'video/x-sgi-movie'], 'log' => [0 => 'text/x-log'], 'txt' => [0 => 'text/plain'], 'css' => [0 => 'text/css'], 'html' => [0 => 'text/html'], 'wav' => [0 => 'audio/x-wav', 1 => 'audio/wave', 2 => 'audio/wav'], 'xhtml' => [0 => 'application/xhtml+xml'], 'tar' => [0 => 'application/x-tar'], 'tgz' => [0 => 'application/x-gzip-compressed'], 'psd' => [0 => 'application/x-photoshop', 1 => 'image/vnd.adobe.photoshop'], 'exe' => [0 => 'application/x-msdownload'], 'js' => [0 => 'application/x-javascript'], 'mp3' => [0 => 'audio/mpeg', 1 => 'audio/mpg', 2 => 'audio/mpeg3', 3 => 'audio/mp3'], 'rar' => [0 => 'application/x-rar', 1 => 'application/rar', 2 => 'application/x-rar-compressed'], 'gzip' => [0 => 'application/x-gzip'], 'hqx' => [0 => 'application/mac-binhex40', 1 => 'application/mac-binhex', 2 => 'application/x-binhex40', 3 => 'application/x-mac-binhex40'], 'cpt' => [0 => 'application/mac-compactpro'], 'bin' => [0 => 'application/macbinary', 1 => 'application/mac-binary', 2 => 'application/x-binary', 3 => 'application/x-macbinary'], 'oda' => [0 => 'application/oda'], 'ai' => [0 => 'application/postscript'], 'smil' => [0 => 'application/smil'], 'mif' => [0 => 'application/vnd.mif'], 'wbxml' => [0 => 'application/wbxml'], 'wmlc' => [0 => 'application/wmlc'], 'dcr' => [0 => 'application/x-director'], 'dvi' => [0 => 'application/x-dvi'], 'gtar' => [0 => 'application/x-gtar'], 'php' => [0 => 'application/x-httpd-php', 1 => 'application/php', 2 => 'application/x-php', 3 => 'text/php', 4 => 'text/x-php', 5 => 'application/x-httpd-php-source'], 'swf' => [0 => 'application/x-shockwave-flash'], 'sit' => [0 => 'application/x-stuffit'], 'z' => [0 => 'application/x-compress'], 'mid' => [0 => 'audio/midi'], 'aif' => [0 => 'audio/x-aiff', 1 => 'audio/aiff'], 'ram' => [0 => 'audio/x-pn-realaudio'], 'rpm' => [0 => 'audio/x-pn-realaudio-plugin'], 'ra' => [0 => 'audio/x-realaudio'], 'rv' => [0 => 'video/vnd.rn-realvideo'], 'jp2' => [0 => 'image/jp2', 1 => 'video/mj2', 2 => 'image/jpx', 3 => 'image/jpm'], 'tiff' => [0 => 'image/tiff'], 'eml' => [0 => 'message/rfc822'], 'pem' => [0 => 'application/x-x509-user-cert', 1 => 'application/x-pem-file'], 'p10' => [0 => 'application/x-pkcs10', 1 => 'application/pkcs10'], 'p12' => [0 => 'application/x-pkcs12'], 'p7a' => [0 => 'application/x-pkcs7-signature'], 'p7c' => [0 => 'application/pkcs7-mime', 1 => 'application/x-pkcs7-mime'], 'p7r' => [0 => 'application/x-pkcs7-certreqresp'], 'p7s' => [0 => 'application/pkcs7-signature'], 'crt' => [0 => 'application/x-x509-ca-cert', 1 => 'application/pkix-cert'], 'crl' => [0 => 'application/pkix-crl', 1 => 'application/pkcs-crl'], 'pgp' => [0 => 'application/pgp'], 'gpg' => [0 => 'application/gpg-keys'], 'rsa' => [0 => 'application/x-pkcs7'], 'ics' => [0 => 'text/calendar'], 'zsh' => [0 => 'text/x-scriptzsh'], 'cdr' => [0 => 'application/cdr', 1 => 'application/coreldraw', 2 => 'application/x-cdr', 3 => 'application/x-coreldraw', 4 => 'image/cdr', 5 => 'image/x-cdr', 6 => 'zz-application/zz-winassoc-cdr'], 'wma' => [0 => 'audio/x-ms-wma'], 'vcf' => [0 => 'text/x-vcard'], 'srt' => [0 => 'text/srt'], 'vtt' => [0 => 'text/vtt'], 'ico' => [0 => 'image/x-icon', 1 => 'image/x-ico', 2 => 'image/vnd.microsoft.icon'], 'csv' => [0 => 'text/x-comma-separated-values', 1 => 'text/comma-separated-values', 2 => 'application/vnd.msexcel'], 'json' => [0 => 'application/json', 1 => 'text/json']];
}
