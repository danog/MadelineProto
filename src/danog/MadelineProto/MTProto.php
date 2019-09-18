<?php

/**
 * MTProto module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Loop;
use danog\MadelineProto\Async\AsyncConstruct;
use danog\MadelineProto\Loop\Generic\PeriodicLoop;
use danog\MadelineProto\Loop\Update\FeedLoop;
use danog\MadelineProto\Loop\Update\SeqLoop;
use danog\MadelineProto\Loop\Update\UpdateLoop;
use danog\MadelineProto\MTProtoTools\CombinedUpdatesState;
use danog\MadelineProto\MTProtoTools\MinDatabase;
use danog\MadelineProto\MTProtoTools\ReferenceDatabase;
use danog\MadelineProto\MTProtoTools\UpdatesState;
use danog\MadelineProto\TL\TLCallback;

/**
 * Manages all of the mtproto stuff.
 */
class MTProto extends AsyncConstruct implements TLCallback
{
    use \danog\Serializable;
    use \danog\MadelineProto\MTProtoTools\AuthKeyHandler;
    use \danog\MadelineProto\MTProtoTools\CallHandler;
    use \danog\MadelineProto\MTProtoTools\Crypt;
    use \danog\MadelineProto\MTProtoTools\PeerHandler;
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


    /**
     * Old internal version of MadelineProto.
     *
     * DO NOT REMOVE THIS COMMENTED OUT CONSTANT
     *
     * @var int
     */
    /*
    const V = 71;
     */
    /**
     * Internal version of MadelineProto.
     *
     * Increased every time the default settings array or something big changes
     *
     * @var int
     */
    const V = 132;
    /**
     * String release version.
     *
     * @var string
     */
    const RELEASE = '5.0';
    /**
     * We're not logged in.
     *
     * @var int
     */
    const NOT_LOGGED_IN = 0;
    /**
     * We're waiting for the login code.
     *
     * @var int
     */
    const WAITING_CODE = 1;
    /**
     * We're waiting for parameters to sign up.
     *
     * @var int
     */
    const WAITING_SIGNUP = -1;
    /**
     * We're waiting for the 2FA password.
     *
     * @var int
     */
    const WAITING_PASSWORD = 2;
    /**
     * We're logged in.
     *
     * @var int
     */
    const LOGGED_IN = 3;
    /**
     * Disallowed methods.
     *
     * @var array
     */
    const DISALLOWED_METHODS = ['account.updatePasswordSettings' => 'You cannot use this method directly; use $MadelineProto->update_2fa($params), instead (see https://docs.madelineproto.xyz for more info)', 'account.getPasswordSettings' => 'You cannot use this method directly; use $MadelineProto->update_2fa($params), instead (see https://docs.madelineproto.xyz for more info)', 'messages.receivedQueue' => 'You cannot use this method directly', 'messages.getDhConfig' => 'You cannot use this method directly, instead use $MadelineProto->get_dh_config();', 'auth.bindTempAuthKey' => 'You cannot use this method directly, instead modify the PFS and default_temp_auth_key_expires_in settings, see https://docs.madelineproto.xyz/docs/SETTINGS.html for more info', 'auth.exportAuthorization' => 'You cannot use this method directly, use $MadelineProto->export_authorization() instead, see https://docs.madelineproto.xyz/docs/LOGIN.html', 'auth.importAuthorization' => 'You cannot use this method directly, use $MadelineProto->import_authorization($authorization) instead, see https://docs.madelineproto.xyz/docs/LOGIN.html', 'auth.logOut' => 'You cannot use this method directly, use the logout method instead (see https://docs.madelineproto.xyz for more info)', 'auth.importBotAuthorization' => 'You cannot use this method directly, use the bot_login method instead (see https://docs.madelineproto.xyz for more info)', 'auth.sendCode' => 'You cannot use this method directly, use the phone_login method instead (see https://docs.madelineproto.xyz for more info)', 'auth.signIn' => 'You cannot use this method directly, use the complete_phone_login method instead (see https://docs.madelineproto.xyz for more info)', 'auth.checkPassword' => 'You cannot use this method directly, use the complete_2fa_login method instead (see https://docs.madelineproto.xyz for more info)', 'auth.signUp' => 'You cannot use this method directly, use the complete_signup method instead (see https://docs.madelineproto.xyz for more info)', 'users.getFullUser' => 'You cannot use this method directly, use the get_pwr_chat, get_info, get_full_info methods instead (see https://docs.madelineproto.xyz for more info)', 'channels.getFullChannel' => 'You cannot use this method directly, use the get_pwr_chat, get_info, get_full_info methods instead (see https://docs.madelineproto.xyz for more info)', 'messages.getFullChat' => 'You cannot use this method directly, use the get_pwr_chat, get_info, get_full_info methods instead (see https://docs.madelineproto.xyz for more info)', 'contacts.resolveUsername' => 'You cannot use this method directly, use the resolve_username, get_pwr_chat, get_info, get_full_info methods instead (see https://docs.madelineproto.xyz for more info)', 'messages.acceptEncryption' => 'You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling secret chats', 'messages.discardEncryption' => 'You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling secret chats', 'messages.requestEncryption' => 'You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling secret chats', 'phone.requestCall' => 'You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls', 'phone.acceptCall' => 'You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls', 'phone.confirmCall' => 'You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls', 'phone.discardCall' => 'You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls', 'updates.getChannelDifference' => 'You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling updates', 'updates.getDifference' => 'You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling updates', 'updates.getState' => 'You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling updates', 'upload.getCdnFile' => 'You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info', 'upload.getFileHashes' => 'You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info', 'upload.getCdnFileHashes' => 'You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info', 'upload.reuploadCdnFile' => 'You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info', 'upload.getFile' => 'You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info', 'upload.saveFilePart' => 'You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info', 'upload.saveBigFilePart' => 'You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://docs.madelineproto.xyz for more info'];
    /**
     * Bad message error codes.
     *
     * @var array
     */
    const BAD_MSG_ERROR_CODES = [
        16 => 'msg_id too low (most likely, client time is wrong; it would be worthwhile to synchronize it using msg_id notifications and re-send the original message with the â€œcorrectâ€ msg_id or wrap it in a container with a new msg_id if the original message had waited too long on the client to be transmitted)',
        17 => 'msg_id too high (similar to the previous case, the client time has to be synchronized, and the message re-sent with the correct msg_id)',
        18 => 'incorrect two lower order msg_id bits (the server expects client message msg_id to be divisible by 4)',
        19 => 'container msg_id is the same as msg_id of a previously received message (this must never happen)',
        20 => 'message too old, and it cannot be verified whether the server has received a message with this msg_id or not',
        32 => 'msg_seqno too low (the server has already received a message with a lower msg_id but with either a higher or an equal and odd seqno)',
        33 => 'msg_seqno too high (similarly, there is a message with a higher msg_id but with either a lower or an equal and odd seqno)',
        34 => 'an even msg_seqno expected (irrelevant message), but odd received',
        35 => 'odd msg_seqno expected (relevant message), but even received',
        48 => 'incorrect server salt (in this case, the bad_server_salt response is received with the correct salt, and the message is to be re-sent with it)',
        64 => 'invalid container'
    ];

    /**
     * Localized message info flags.
     *
     * @var array
     */
    const MSGS_INFO_FLAGS = [
        1 => 'nothing is known about the message (msg_id too low, the other party may have forgotten it)',
        2 => 'message not received (msg_id falls within the range of stored identifiers; however, the other party has certainly not received a message like that)',
        3 => 'message not received (msg_id too high; however, the other party has certainly not received it yet)',
        4 => 'message received (note that this response is also at the same time a receipt acknowledgment)',
        8 => ' and message already acknowledged',
        16 => ' and message not requiring acknowledgment',
        32 => ' and RPC query contained in message being processed or processing already complete',
        64 => ' and content-related response to message already generated',
        128 => ' and other party knows for a fact that message is already received'
    ];
    const REQUESTED = 0;
    const ACCEPTED = 1;
    const CONFIRMED = 2;
    const READY = 3;
    const TD_PARAMS_CONVERSION = ['updateNewMessage' => ['_' => 'updateNewMessage', 'disable_notification' => ['message', 'silent'], 'message' => ['message']], 'message' => ['_' => 'message', 'id' => ['id'], 'sender_user_id' => ['from_id'], 'chat_id' => ['to_id', 'choose_chat_id_from_botapi'], 'send_state' => ['choose_incoming_or_sent'], 'can_be_edited' => ['choose_can_edit'], 'can_be_deleted' => ['choose_can_delete'], 'is_post' => ['post'], 'date' => ['date'], 'edit_date' => ['edit_date'], 'forward_info' => ['fwd_info', 'choose_forward_info'], 'reply_to_message_id' => ['reply_to_msg_id'], 'ttl' => ['choose_ttl'], 'ttl_expires_in' => ['choose_ttl_expires_in'], 'via_bot_user_id' => ['via_bot_id'], 'views' => ['views'], 'content' => ['choose_message_content'], 'reply_markup' => ['reply_markup']], 'messages.sendMessage' => ['chat_id' => ['peer'], 'reply_to_message_id' => ['reply_to_msg_id'], 'disable_notification' => ['silent'], 'from_background' => ['background'], 'input_message_content' => ['choose_message_content'], 'reply_markup' => ['reply_markup']]];
    const TD_REVERSE = ['sendMessage' => 'messages.sendMessage'];
    const TD_IGNORE = ['updateMessageID'];
    const BOTAPI_PARAMS_CONVERSION = ['disable_web_page_preview' => 'no_webpage', 'disable_notification' => 'silent', 'reply_to_message_id' => 'reply_to_msg_id', 'chat_id' => 'peer', 'text' => 'message'];
    const NOT_CONTENT_RELATED = [
        //'rpc_result',
        //'rpc_error',
        'rpc_drop_answer',
        'rpc_answer_unknown',
        'rpc_answer_dropped_running',
        'rpc_answer_dropped',
        'get_future_salts',
        'future_salt',
        'future_salts',
        'ping',
        'pong',
        'ping_delay_disconnect',
        'destroy_session',
        'destroy_session_ok',
        'destroy_session_none',
        //'new_session_created',
        'msg_container',
        'msg_copy',
        'gzip_packed',
        'http_wait',
        'msgs_ack',
        'bad_msg_notification',
        'bad_server_salt',
        'msgs_state_req',
        'msgs_state_info',
        'msgs_all_info',
        'msg_detailed_info',
        'msg_new_detailed_info',
        'msg_resend_req',
        'msg_resend_ans_req',
    ];
    const DEFAULT_GETUPDATES_PARAMS = ['offset' => 0, 'limit' => null, 'timeout' => 0];

    /**
     * Instance of wrapper API.
     *
     * @var API|null
     */
    public $wrapper;
    /**
     * PWRTelegram webhook URL.
     *
     * @var boolean|string
     */
    public $hook_url = false;
    /**
     * Settings array.
     *
     * @var array
     */
    public $settings = [];
    /**
     * Config array.
     *
     * @var array
     */
    private $config = ['expires' => -1];
    /**
     * TOS info.
     *
     * @var array
     */
    private $tos = ['expires' => 0, 'accepted' => true];
    /**
     * Whether we're initing authorization.
     *
     * @var boolean
     */
    private $initing_authorization = false;
    /**
     * Authorization info (User).
     *
     * @var [type]
     */
    public $authorization = null;
    /**
     * Whether we're authorized.
     *
     * @var integer
     */
    public $authorized = self::NOT_LOGGED_IN;
    /**
     * Main authorized DC ID.
     *
     * @var integer
     */
    public $authorized_dc = -1;
    /**
     * RSA keys.
     *
     * @var array<RSA>
     */
    private $rsa_keys = [];
    /**
     * CDN RSA keys.
     *
     * @var array
     */
    private $cdn_rsa_keys = [];
    /**
     * Diffie-hellman config.
     *
     * @var array
     */
    private $dh_config = ['version' => 0];
    /**
     * Internal peer database.
     *
     * @var array
     */
    public $chats = [];
    /**
     * Cached parameters for fetching channel participants.
     *
     * @var array
     */
    public $channel_participants = [];

    /**
     * When we last stored data in remote peer database (now doesn't exist anymore).
     *
     * @var integer
     */
    public $last_stored = 0;
    /**
     * Temporary array of data to be sent to remote peer database.
     *
     * @var array
     */
    public $qres = [];
    /**
     * Full chat info database.
     *
     * @var array
     */
    public $full_chats = [];
    /**
     * Latest chat message ID map for update handling.
     *
     * @var array
     */
    private $msg_ids = [];
    /**
     * Version integer for upgrades.
     *
     * @var integer
     */
    private $v = 0;
    /**
     * Cached getdialogs params.
     *
     * @var array
     */
    private $dialog_params = ['limit' => 0, 'offset_date' => 0, 'offset_id' => 0, 'offset_peer' => ['_' => 'inputPeerEmpty'], 'count' => 0];
    /**
     * Whether new settings were set and should be applied.
     *
     * @var boolean
     */
    public $flushSettings = false;
    /**
     * Storage for arbitrary data.
     *
     * @var array
     */
    public $storage = [];
    /**
     * Support user ID.
     *
     * @var integer
     */
    private $supportUser = 0;
    /**
     * File reference database.
     *
     * @var \danog\MadelineProto\MTProtoTools\ReferenceDatabase
     */
    public $referenceDatabase;
    /**
     * min database.
     *
     * @var \danog\MadelineProto\MTProtoTools\MinDatabase
     */
    public $minDatabase;
    /**
     * TOS check loop.
     *
     * @var \danog\MadelineProto\Loop\Update\PeriodicLoop
     */
    public $checkTosLoop;
    /**
     * Phone config loop.
     *
     * @var \danog\MadelineProto\Loop\Update\PeriodicLoop
     */
    public $phoneConfigLoop;
    /**
     * Config loop.
     *
     * @var \danog\MadelineProto\Loop\Update\PeriodicLoop
     */
    public $configLoop;
    /**
     * Call checker loop.
     *
     * @var \danog\MadelineProto\Loop\Update\PeriodicLoop
     */
    private $callCheckerLoop;
    /**
     * Autoserialization loop.
     *
     * @var \danog\MadelineProto\Loop\Update\PeriodicLoop
     */
    private $serializeLoop;
    /**
     * Feeder loops.
     *
     * @var array<\danog\MadelineProto\Loop\Update\FeedLoop>
     */
    public $feeders = [];
    /**
     * Updater loops.
     *
     * @var array<\danog\MadelineProto\Loop\Update\UpdateLoop>
     */
    public $updaters = [];
    /**
     * Boolean to avoid problems with exceptions thrown by forked strands, see tools.
     *
     * @var boolean
     */
    public $destructing = false;

    /**
     * DataCenter instance.
     *
     * @var DataCenter
     */
    public $datacenter;

    /**
     * Constructor function.
     *
     * @param array $settings Settings
     *
     * @return void
     */
    public function __magic_construct($settings = [])
    {
        $this->setInitPromise($this->__construct_async($settings));
    }

    /**
     * Async constructor function.
     *
     * @param array $settings Settings
     *
     * @return void
     */
    public function __construct_async($settings = [])
    {
        \danog\MadelineProto\Magic::class_exists();
        // Parse settings
        $this->parse_settings($settings);
        // Connect to servers
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['inst_dc'], Logger::ULTRA_VERBOSE);
        if (!($this->channels_state instanceof CombinedUpdatesState)) {
            $this->channels_state = new CombinedUpdatesState($this->channels_state);
        }
        if (isset($this->updates_state)) {
            if (!($this->updates_state instanceof UpdatesState)) {
                $this->updates_state = new UpdatesState($this->updates_state);
            }
            $this->channels_state->__construct([false => $this->updates_state]);
            unset($this->updates_state);
        }
        if (!isset($this->datacenter)) {
            $this->datacenter = new DataCenter($this, $this->settings['connection'], $this->settings['connection_settings']);
        }
        if (!isset($this->referenceDatabase)) {
            $this->referenceDatabase = new ReferenceDatabase($this);
        }
        if (!isset($this->minDatabase)) {
            $this->minDatabase = new MinDatabase($this);
        }
        // Load rsa keys
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['load_rsa'], Logger::ULTRA_VERBOSE);
        $this->rsa_keys = [];
        foreach ($this->settings['authorization']['rsa_keys'] as $key) {
            $key = yield (new RSA())->load($key);
            $this->rsa_keys[$key->fp] = $key;
        }
        /*
         * ***********************************************************************
         * Define some needed numbers for BigInteger
         */
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['TL_translation'], Logger::ULTRA_VERBOSE);
        $callbacks = [$this, $this->referenceDatabase];
        if (!($this->authorization['user']['bot'] ?? false)) {
            $callbacks []= $this->minDatabase;
        }
        $this->construct_TL($this->settings['tl_schema']['src'], $callbacks);
        yield $this->connect_to_all_dcs_async();
        $this->startLoops();
        $this->datacenter->curdc = 2;
        if ((!isset($this->authorization['user']['bot']) || !$this->authorization['user']['bot']) && $this->datacenter->getDataCenterConnection($this->datacenter->curdc)->hasTempAuthKey()) {
            try {
                $nearest_dc = yield $this->method_call_async_read('help.getNearestDc', [], ['datacenter' => $this->datacenter->curdc]);
                $this->logger->logger(\sprintf(\danog\MadelineProto\Lang::$current_lang['nearest_dc'], $nearest_dc['country'], $nearest_dc['nearest_dc']), Logger::NOTICE);
                if ($nearest_dc['nearest_dc'] != $nearest_dc['this_dc']) {
                    $this->settings['connection_settings']['default_dc'] = $this->datacenter->curdc = (int) $nearest_dc['nearest_dc'];
                }
            } catch (RPCErrorException $e) {
                if ($e->rpc !== 'BOT_METHOD_INVALID') {
                    throw $e;
                }
            }
        }
        yield $this->get_config_async([], ['datacenter' => $this->datacenter->curdc]);
        $this->startUpdateSystem(true);
        $this->v = self::V;
    }

    public function __sleep()
    {
        if ($this->settings['serialization']['cleanup_before_serialization']) {
            $this->cleanup();
        }
        return ['supportUser', 'referenceDatabase', 'minDatabase', 'channel_participants', 'event_handler', 'event_handler_instance', 'loop_callback', 'web_template', 'encrypted_layer', 'settings', 'config', 'authorization', 'authorized', 'rsa_keys', 'dh_config', 'chats', 'last_stored', 'qres', 'got_state', 'channels_state', 'updates', 'updates_key', 'full_chats', 'msg_ids', 'dialog_params', 'datacenter', 'v', 'constructors', 'td_constructors', 'methods', 'td_methods', 'td_descriptions', 'tl_callbacks', 'temp_requested_secret_chats', 'temp_rekeyed_secret_chats', 'secret_chats', 'hook_url', 'storage', 'authorized_dc', 'tos'];
    }


    /**
     * Cleanup memory and session file.
     *
     * @return void
     */
    private function cleanup()
    {
        $this->referenceDatabase = new ReferenceDatabase($this);
        $callbacks = [$this, $this->referenceDatabase];
        if (!($this->authorization['user']['bot'] ?? false)) {
            $callbacks []= $this->minDatabase;
        }
        $this->update_callbacks($callbacks);
        return $this;
    }

    public function logger($param, $level = Logger::NOTICE, $file = null)
    {
        if ($file === null) {
            $file = \basename(\debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'], '.php');
        }

        return isset($this->logger) ? $this->logger->logger($param, $level, $file) : Logger::$default->logger($param, $level, $file);
    }
    public function isAltervista()
    {
        return Magic::$altervista;
    }

    public function isInitingAuthorization()
    {
        return $this->initing_authorization;
    }

    public function getHTTPClient()
    {
        return $this->datacenter->getHTTPClient();
    }

    public function getDNSClient()
    {
        return $this->datacenter->getDNSClient();
    }

    public function fileGetContents($url): \Generator
    {
        return $this->datacenter->fileGetContents($url);
    }
    public function a(callable $a, ?string $b = null, $c = null, $d = 2, $e = self::METHOD_BEFORE_CALLBACK): ?string
    {
    }
    /**
     * Get all datacenter connections.
     *
     * @return array<DataCenterConnection>
     */
    public function getDataCenterConnections(): array
    {
        return $this->datacenter->getDataCenterConnections();
    }

    public function hasAllAuth()
    {
        if ($this->isInitingAuthorization()) {
            return false;
        }

        foreach ($this->datacenter->getDataCenterConnections() as $dc) {
            if (!$dc->isAuthorized() || !$dc->hasTempAuthKey()) {
                return false;
            }
        }

        return true;
    }
    public function serialize()
    {
        if ($this->wrapper instanceof API && isset($this->wrapper->session) && !\is_null($this->wrapper->session) && !$this->asyncInitPromise) {
            //$this->logger->logger("Didn't serialize in a while, doing that now...");
            $this->wrapper->serialize($this->wrapper->session);
        }
    }
    public function startLoops()
    {
        if (!$this->callCheckerLoop) {
            $this->callCheckerLoop = new PeriodicLoop($this, [$this, 'checkCalls'], 'call check', 10);
        }
        if (!$this->serializeLoop) {
            $this->serializeLoop = new PeriodicLoop($this, [$this, 'serialize'], 'serialize', $this->settings['serialization']['serialization_interval']);
        }
        if (!$this->phoneConfigLoop) {
            $this->phoneConfigLoop = new PeriodicLoop($this, [$this, 'get_phone_config_async'], 'phone config', 24 * 3600);
        }
        if (!$this->checkTosLoop) {
            $this->checkTosLoop = new PeriodicLoop($this, [$this, 'check_tos_async'], 'TOS', 24 * 3600);
        }
        if (!$this->configLoop) {
            $this->configLoop = new PeriodicLoop($this, [$this, 'get_config_async'], 'config', 24 * 3600);
        }

        $this->callCheckerLoop->start();
        $this->serializeLoop->start();
        $this->phoneConfigLoop->start();
        $this->configLoop->start();
        $this->checkTosLoop->start();
    }
    public function stopLoops()
    {
        if ($this->callCheckerLoop) {
            $this->callCheckerLoop->signal(true);
            $this->callCheckerLoop = null;
        }
        if ($this->serializeLoop) {
            $this->serializeLoop->signal(true);
            $this->serializeLoop = null;
        }
        if ($this->phoneConfigLoop) {
            $this->phoneConfigLoop->signal(true);
            $this->phoneConfigLoop = null;
        }
        if ($this->configLoop) {
            $this->configLoop->signal(true);
            $this->configLoop = null;
        }
        if ($this->checkTosLoop) {
            $this->checkTosLoop->signal(true);
            $this->checkTosLoop = null;
        }
    }
    public function __wakeup()
    {
        $backtrace = \debug_backtrace(0, 3);
        $this->asyncInitPromise = true;
        $this->setInitPromise($this->__wakeup_async($backtrace));
    }

    public function __wakeup_async($backtrace)
    {
        \set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
        $this->setup_logger();
        if (\danog\MadelineProto\Magic::$has_thread && \is_object(\Thread::getCurrentThread())) {
            return;
        }
        Lang::$current_lang = &Lang::$lang['en'];
        if (isset($this->settings['app_info']['lang_code']) && isset(Lang::$lang[$this->settings['app_info']['lang_code']])) {
            Lang::$current_lang = &Lang::$lang[$this->settings['app_info']['lang_code']];
        }
        if (!isset($this->referenceDatabase)) {
            $this->referenceDatabase = new ReferenceDatabase($this);
        }
        if (!isset($this->minDatabase)) {
            $this->minDatabase = new MinDatabase($this);
        }
        $callbacks = [$this, $this->referenceDatabase];
        if (!($this->authorization['user']['bot'] ?? false)) {
            $callbacks []= $this->minDatabase;
        }
        $this->update_callbacks($callbacks);

        $this->settings['connection_settings']['all']['ipv6'] = \danog\MadelineProto\Magic::$ipv6;
        /*if (isset($this->settings['pwr']['update_handler']) && $this->settings['pwr']['update_handler'] === $this->settings['updates']['callback']) {
        unset($this->settings['pwr']['update_handler']);
        $this->updates = [];
        }*/
        /*$keys = array_keys((array) get_object_vars($this));
        if (count($keys) !== count(array_unique($keys))) {
        throw new Bug74586Exception();
        }
        if (isset($this->data)) {
        foreach ($this->data as $k => $v) {
        $this->{$k} = $v;
        }
        unset($this->data);
        }*/
        if ($this->authorized === true) {
            $this->authorized = self::LOGGED_IN;
        }
        if (!($this->channels_state instanceof CombinedUpdatesState)) {
            $this->channels_state = new CombinedUpdatesState($this->channels_state);
        }
        if (isset($this->updates_state)) {
            if (!($this->updates_state instanceof UpdatesState)) {
                $this->updates_state = new UpdatesState($this->updates_state);
            }
            $this->channels_state->__construct([false => $this->updates_state]);
            unset($this->updates_state);
        }

        if ($this->event_handler && \class_exists($this->event_handler) && \is_subclass_of($this->event_handler, '\danog\MadelineProto\EventHandler')) {
            $this->setEventHandler($this->event_handler);
        }
        $force = false;
        $this->resetMTProtoSession();
        if (isset($backtrace[2]['function'], $backtrace[2]['class'], $backtrace[2]['args']) && $backtrace[2]['class'] === 'danog\\MadelineProto\\API' && $backtrace[2]['function'] === '__construct_async') {
            if (\count($backtrace[2]['args']) >= 2) {
                $this->parse_settings(\array_replace_recursive($this->settings, $backtrace[2]['args'][1]));
            }
        }

        if (isset($this->settings['tl_schema']['src']['botAPI']) && $this->settings['tl_schema']['src']['botAPI'] !== __DIR__.'/TL_botAPI.tl') {
            unset($this->v);
        }

        if (!isset($this->v) || $this->v !== self::V) {
            $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['serialization_ofd'], Logger::WARNING);
            foreach ($this->datacenter->getDataCenterConnections() as $dc_id => $socket) {
                if ($this->authorized === self::LOGGED_IN && \strpos($dc_id, '_') === false && $socket->hasPermAuthKey() && $socket->hasTempAuthKey()) {
                    $socket->bind();
                    $socket->authorized(true);
                }
            }
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
                if (isset($full['full'], $full['last_update'])) {
                    $this->full_chats[$id] = ['full' => $full['full'], 'last_update' => $full['last_update']];
                }
            }
            foreach ($this->secret_chats as $key => &$chat) {
                if (!\is_array($chat)) {
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
                if (\in_array($key, ['default_dc', 'media_socket_count', 'robin_period'])) {
                    continue;
                }
                if (!\is_array($connection)) {
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
                    $connection['pfs'] = \extension_loaded('gmp');
                }
                if ($connection['protocol'] === 'obfuscated2') {
                    $connection['protocol'] = 'tcp_intermediate_padded';
                    $connection['obfuscated'] = true;
                }
            }
            if ($settings['app_info']['api_id'] === 6) {
                unset($settings['app_info']);
            }
            $this->resetMTProtoSession(true, true);
            $this->config = ['expires' => -1];
            $this->dh_config = ['version' => 0];
            yield $this->__construct_async($settings);
            $force = true;
            foreach ($this->secret_chats as $chat => $data) {
                try {
                    if (isset($this->secret_chats[$chat]) && $this->secret_chats[$chat]['InputEncryptedChat'] !== null) {
                        yield $this->notify_layer_async($chat);
                    }
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                }
            }
        }

        /*if (!$this->settings['updates']['handle_old_updates']) {
        $this->channels_state = new CombinedUpdatesState();
        $this->msg_ids = [];
        $this->got_state = false;
        }*/
        yield $this->connect_to_all_dcs_async();
        foreach ($this->calls as $id => $controller) {
            if (!\is_object($controller)) {
                unset($this->calls[$id]);
            } elseif ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                $controller->setMadeline($this);
                $controller->discard();
            } else {
                $controller->setMadeline($this);
            }
        }
        $this->startLoops();
        if (yield $this->get_self_async()) {
            $this->authorized = self::LOGGED_IN;
        }

        if ($this->authorized === self::LOGGED_IN) {
            yield $this->get_cdn_config_async($this->datacenter->curdc);
            $this->setup_logger();
        }
        $this->startUpdateSystem(true);
        if ($this->authorized === self::LOGGED_IN && !$this->authorization['user']['bot'] && $this->settings['peer']['cache_all_peers_on_startup']) {
            yield $this->get_dialogs_async($force);
        }
        if ($this->authorized === self::LOGGED_IN && $this->settings['updates']['handle_updates']) {
            $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['getupdates_deserialization'], Logger::NOTICE);
            yield $this->updaters[false]->resume();
        }
        $this->updaters[false]->start();
    }

    public function __destruct()
    {
        $this->stopLoops();
        if (isset($this->seqUpdater)) {
            $this->seqUpdater->signal(true);
        }
        $channelIds = [];
        foreach ($this->channels_state->get() as $state) {
            $channelIds[] = $state->getChannel();
        }
        \sort($channelIds);
        foreach ($channelIds as $channelId) {
            if (isset($this->feeders[$channelId])) {
                $this->feeders[$channelId]->signal(true);
            }
            if (!isset($this->updaters[$channelId])) {
                $this->updaters[$channelId]->signal(true);
            }
        }
        foreach ($this->datacenter->getDataCenterConnections() as $datacenter) {
            $datacenter->disconnect();
        }
        $this->logger("Successfully destroyed MadelineProto");
    }

    public static function getSettings($settings, $previousSettings = [])
    {
        Magic::class_exists();
        if (isset($previousSettings['connection_settings']['default_dc'])) {
            $settings['connection_settings']['default_dc'] = $previousSettings['connection_settings']['default_dc'];
        }
        if (!isset($settings['app_info']['api_id']) || !$settings['app_info']['api_id']) {
            if (isset($previousSettings['app_info']['api_id']) && $previousSettings['app_info']['api_id']) {
                $settings['app_info']['api_id'] = $previousSettings['app_info']['api_id'];
                $settings['app_info']['api_hash'] = $previousSettings['app_info']['api_hash'];
            } else {
                $settings['app_info'] = [];
            }
        }
        // Detect device model
        try {
            $device_model = \php_uname('s');
        } catch (\danog\MadelineProto\Exception $e) {
            $device_model = 'Web server';
        }
        if (($settings['app_info']['api_id'] ?? 0) === 6) {
            // TG DEV NOTICE: these app info spoofing measures were implemented for NON-MALICIOUS purposes.
            // All accounts registered with a custom API ID require manual verification through recover@telegram.org, to avoid instant permabans.
            // This makes usage of all MTProto libraries very difficult, at least for new users.
            // To help a bit, when the android API ID is used, the android app infos are spoofed too.
            // THE ANDROID API HASH IS NOT PRESENT IN THIS REPOSITORY, AND WILL NOT BE GIVEN TO EVERYONE.
            // This measure was NOT created with the intent to aid spammers, flooders, and other scum.
            //
            // I understand that automated account registration through headless libraries may indicate the creation of a botnet,
            // ...and I understand why these automatic bans were implemented in the first place.
            // Manual requests to activate numbers through recover@telegram.org will still be required for the majority of users of this library,
            // ...those that choose to user their own API ID for their application.
            //
            // To be honest, I wrote this feature just for me, since I honestly don't want to
            // ...go through the hassle of registering => recovering => logging in to every account I use for my services (mainly webradios and test userbots)
            $device_model = 'LGENexus 5';
        }
        // Detect system version
        try {
            $system_version = \php_uname('r');
        } catch (\danog\MadelineProto\Exception $e) {
            $system_version = PHP_VERSION;
        }
        if (($settings['app_info']['api_id'] ?? 0) === 6) {
            // TG DEV NOTICE: these app info spoofing measures were implemented for NON-MALICIOUS purposes.
            // All accounts registered with a custom API ID require manual verification through recover@telegram.org, to avoid instant permabans.
            // This makes usage of all MTProto libraries very difficult, at least for new users.
            // To help a bit, when the android API ID is used, the android app infos are spoofed too.
            // THE ANDROID API HASH IS NOT PRESENT IN THIS REPOSITORY, AND WILL NOT BE GIVEN TO EVERYONE.
            // This measure was NOT created with the intent to aid spammers, flooders, and other scum.
            //
            // I understand that automated account registration through headless libraries may indicate the creation of a botnet,
            // ...and I understand why these automatic bans were implemented in the first place.
            // Manual requests to activate numbers through recover@telegram.org will still be required for the majority of users of this library,
            // ...and in particular those that choose to user their own API ID for their application.
            //
            // To be honest, I wrote this feature just for me, since I honestly don't want to
            // ...go through the hassle of registering => recovering => logging in to every account I use for my services (mainly webradios and test userbots)

            $system_version = 'SDK 28';
        }
        // Detect language
        $lang_code = 'en';
        Lang::$current_lang = &Lang::$lang[$lang_code];
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $lang_code = \substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        } elseif (isset($_SERVER['LANG'])) {
            $lang_code = \explode('_', $_SERVER['LANG'])[0];
        }
        if (isset(Lang::$lang[$lang_code])) {
            Lang::$current_lang = &Lang::$lang[$lang_code];
        }
        // Detect language pack
        $lang_pack = '';
        if (($settings['app_info']['api_id'] ?? 0) === 6) {
            // TG DEV NOTICE: these app info spoofing measures were implemented for NON-MALICIOUS purposes.
            // All accounts registered with a custom API ID require manual verification through recover@telegram.org, to avoid instant permabans.
            // This makes usage of all MTProto libraries very difficult, at least for new users.
            // To help a bit, when the android API ID is used, the android app infos are spoofed too.
            // THE ANDROID API HASH IS NOT PRESENT IN THIS REPOSITORY, AND WILL NOT BE GIVEN TO EVERYONE.
            // This measure was NOT created with the intent to aid spammers, flooders, and other scum.
            //
            // I understand that automated account registration through headless libraries may indicate the creation of a botnet,
            // ...and I understand why these automatic bans were implemented in the first place.
            // Manual requests to activate numbers through recover@telegram.org will still be required for the majority of users of this library,
            // ...and in particular those that choose to user their own API ID for their application.
            //
            // To be honest, I wrote this feature just for me, since I honestly don't want to
            // ...go through the hassle of registering => recovering => logging in to every account I use for my services (mainly webradios and test userbots)

            $lang_pack = 'android';
        }
        // Detect app version
        $app_version = self::RELEASE.' ('.self::V.', '.Magic::$revision.')';
        if (($settings['app_info']['api_id'] ?? 0) === 6) {
            // TG DEV NOTICE: these app info spoofing measures were implemented for NON-MALICIOUS purposes.
            // All accounts registered with a custom API ID require manual verification through recover@telegram.org, to avoid instant permabans.
            // This makes usage of all MTProto libraries very difficult, at least for new users.
            // To help a bit, when the android API ID is used, the android app infos are spoofed too.
            // THE ANDROID API HASH IS NOT PRESENT IN THIS REPOSITORY, AND WILL NOT BE GIVEN TO EVERYONE.
            // This measure was NOT created with the intent to aid spammers, flooders, and other scum.
            //
            // I understand that automated account registration through headless libraries may indicate the creation of a botnet,
            // ...and I understand why these automatic bans were implemented in the first place.
            // Manual requests to activate numbers through recover@telegram.org will still be required for the majority of users of this library,
            // ...and in particular those that choose to user their own API ID for their application.
            //
            // To be honest, I wrote this feature just for me, since I honestly don't want to
            // ...go through the hassle of registering => recovering => logging in to every account I use for my services (mainly webradios and test userbots)

            $app_version = '4.9.1 (13613)';
        }

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
                        'port' => 443,
                        'media_only' => false,
                        'tcpo_only' => false,
                    ],
                ],
                'ipv6' => [
                    // ipv6 addresses
                    2 => [
                        // The rest will be fetched using help.getConfig
                        'ip_address' => '2001:067c:04e8:f002:0000:0000:0000:000e',
                        'port' => 443,
                        'media_only' => false,
                        'tcpo_only' => false,
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
                        'port' => 443,
                        'media_only' => false,
                        'tcpo_only' => false,
                    ],
                ],
                'ipv6' => [
                    // ipv6 addresses
                    2 => [
                        // The rest will be fetched using help.getConfig
                        'ip_address' => '2001:067c:04e8:f002:0000:0000:0000:000a',
                        'port' => 443,
                        'media_only' => false,
                        'tcpo_only' => false,
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
                'proxy' => Magic::$altervista ? '\\HttpProxy' : '\\Socket',
                // The proxy class to use
                'proxy_extra' => Magic::$altervista ? ['address' => 'localhost', 'port' => 80] : [],
                // Extra parameters to pass to the proxy class using setExtra
                'obfuscated' => false,
                'transport' => 'tcp',
                'pfs' => \extension_loaded('gmp'),
            ],
            'media_socket_count' => [
                'min' => 5,
                'max' => 10
            ],
            'robin_period' => 10,
            'default_dc' => 2,
        ], 'app_info' => [
            // obtained in https://my.telegram.org
            //'api_id'          => you should put an API id in the settings array you provide
            //'api_hash'        => you should put an API hash in the settings array you provide
            'device_model' => $device_model,
            'system_version' => $system_version,
            'app_version' => $app_version,
            // ðŸŒš
            //                'app_version'     => self::V,
            'lang_code' => $lang_code,
            'lang_pack' => $lang_pack,
        ], 'tl_schema' => [
            // TL scheme files
            'layer' => 105,
            // layer version
            'src' => [
                'mtproto' => __DIR__.'/TL_mtproto_v1.tl',
                // mtproto TL scheme
                'telegram' => __DIR__.'/TL_telegram_v105.tl',
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
            'logger_param' => Magic::$script_cwd.'/MadelineProto.log',
            'logger' => PHP_SAPI === 'cli' ? 3 : 2,
            // overwrite previous setting and echo logs
            'logger_level' => Logger::VERBOSE,
            'max_size' => 100 * 1024 * 1024,
            // Logging level, available logging levels are: ULTRA_VERBOSE, VERBOSE, NOTICE, WARNING, ERROR, FATAL_ERROR. Can be provided as last parameter to the logging function.
            'rollbar_token' => '',
        ], 'max_tries' => [
            'query' => 5,
            // How many times should I try to call a method or send an object before throwing an exception
            'authorization' => 5,
            // How many times should I try to generate an authorization key before throwing an exception
            'response' => 5,
        ], 'flood_timeout' => ['wait_if_lt' => 10 * 60], 'msg_array_limit' => [
            // How big should be the arrays containing the incoming and outgoing messages?
            'incoming' => 100,
            'outgoing' => 100,
            'call_queue' => 200,
        ], 'peer' => [
            'full_info_cache_time' => 3600,
            // Full peer info cache validity
            'full_fetch' => false,
            // Should madeline fetch the full member list of every group it meets?
            'cache_all_peers_on_startup' => false,
        ], 'requests' => ['gzip_encode_if_gt' => 1024 * 1024], 'updates' => [
            'handle_updates' => false,
            // Should I handle updates?
            'handle_old_updates' => true,
            // Should I handle old updates on startup?
            'getdifference_interval' => 10,
            // Getdifference manual polling interval
            'callback' => 'get_updates_update_handler',
            // Update callback
            'run_callback' => true,
        ], 'secret_chats' => ['accept_chats' => true], 'serialization' => [
            'serialization_interval' => 30,
            'cleanup_before_serialization' => false,
        ], 'threading' => [
            'allow_threading' => false,
            // Should I use threading, if it is enabled?
            'handler_workers' => 10,
        ], 'upload' => [
            'allow_automatic_upload' => true,
            'part_size' => 512 * 1024,
            'parallel_chunks' => 20,
        ], 'download' => [
            'report_broken_media' => true,
            'part_size' => 1024 * 1024,
            'parallel_chunks' => 20,
        ], 'pwr' => [
            'pwr' => false,
            // Need info ?
            'db_token' => false,
            // Need info ?
            'strict' => false,
            // Need info ?
            'requests' => true,
        ]];
        $settings = \array_replace_recursive($default_settings, $settings);
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
        return $settings;
    }
    public function parse_settings($settings)
    {
        $settings = self::getSettings($settings, $this->settings);
        if ($settings['app_info'] === null) {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['api_not_set'], 0, null, 'MadelineProto', 1);
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
        $this->logger = Logger::getLoggerFromSettings($this->settings, isset($this->authorization['user']) ? isset($this->authorization['user']['username']) ? $this->authorization['user']['username'] : $this->authorization['user']['id'] : '');
    }

    /**
     * Reset all MTProto sessions.
     *
     * @param boolean $de       Whether to reset the session ID
     * @param boolean $auth_key Whether to reset the auth key
     *
     * @return void
     */
    public function resetMTProtoSession(bool $de = true, bool $auth_key = false)
    {
        if (!\is_object($this->datacenter)) {
            throw new Exception(\danog\MadelineProto\Lang::$current_lang['session_corrupted']);
        }
        foreach ($this->datacenter->getDataCenterConnections() as $id => $socket) {
            if ($de) {
                $socket->resetSession();
            }
            if ($auth_key) {
                $socket->setAuthKey(null);
            }
        }
    }

    /**
     * Check if connected to datacenter using HTTP.
     *
     * @param string $datacenter DC ID
     *
     * @return boolean
     */
    public function isHttp(string $datacenter)
    {
        return $this->datacenter->isHttp($datacenter);
    }

    // Connects to all datacenters and if necessary creates authorization keys, binds them and writes client info
    public function connect_to_all_dcs_async(bool $reconnectAll = true): \Generator
    {
        $this->channels_state->get(false);
        foreach ($this->channels_state->get() as $state) {
            $channelId = $state->getChannel();
            if (!isset($this->feeders[$channelId])) {
                $this->feeders[$channelId] = new FeedLoop($this, $channelId);
            }
            if (!isset($this->updaters[$channelId])) {
                $this->updaters[$channelId] = new UpdateLoop($this, $channelId);
            }
        }
        if (!isset($this->seqUpdater)) {
            $this->seqUpdater = new SeqLoop($this);
        }

        $this->datacenter->__construct($this, $this->settings['connection'], $this->settings['connection_settings'], $reconnectAll);
        $dcs = [];
        foreach ($this->datacenter->get_dcs() as $new_dc) {
            $dcs[] = $this->datacenter->dcConnectAsync($new_dc);
        }
        yield $this->all($dcs);
        yield $this->init_authorization_async();
        yield $this->parse_config_async();
        $dcs = [];
        foreach ($this->datacenter->get_dcs(false) as $new_dc) {
            $dcs[] = $this->datacenter->dcConnectAsync($new_dc);
        }
        yield $this->all($dcs);
        yield $this->init_authorization_async();
        yield $this->parse_config_async();

        yield $this->get_phone_config_async();
    }
    public function resetSession()
    {
        if (isset($this->seqUpdater)) {
            $this->seqUpdater->signal(true);
            unset($this->seqUpdater);
        }
        $channelIds = [];
        foreach ($this->channels_state->get() as $state) {
            $channelIds[] = $state->getChannel();
        }
        \sort($channelIds);
        foreach ($channelIds as $channelId) {
            if (isset($this->feeders[$channelId])) {
                $this->feeders[$channelId]->signal(true);
                unset($this->feeders[$channelId]);
            }
            if (!isset($this->updaters[$channelId])) {
                $this->updaters[$channelId]->signal(true);
                unset($this->updaters[$channelId]);
            }
        }
        foreach ($this->datacenter->getDataCenterConnections() as $socket) {
            $socket->authorized(false);
        }

        $this->channels_state = new CombinedUpdatesState();
        $this->got_state = false;
        $this->msg_ids = [];
        $this->authorized = self::NOT_LOGGED_IN;
        $this->authorized_dc = -1;
        $this->authorization = null;
        $this->updates = [];
        $this->secret_chats = [];
        $this->chats = [];
        $this->users = [];
        $this->tos = ['expires' => 0, 'accepted' => true];
        $this->referenceDatabase = new ReferenceDatabase($this);
        $this->minDatabase = new MinDatabase($this);
        $this->dialog_params = ['_' => 'MadelineProto.dialogParams', 'limit' => 0, 'offset_date' => 0, 'offset_id' => 0, 'offset_peer' => ['_' => 'inputPeerEmpty'], 'count' => 0];
        $this->full_chats = [];
    }
    public function resetUpdateState()
    {
        if (isset($this->seqUpdater)) {
            $this->seqUpdater->signal(true);
        }
        $channelIds = [];
        $newStates = [];
        foreach ($this->channels_state->get() as $state) {
            $channelIds[] = $state->getChannel();
            $channelId = $state->getChannel();
            $pts = $state->pts();
            $pts = $channelId ? \max(1, $pts-1000000) : ($pts > 4000000 ? $pts-1000000 : \max(1, $pts-1000000));
            $newStates[$channelId] = new UpdatesState(['pts' => $pts], $channelId);
        }
        \sort($channelIds);
        foreach ($channelIds as $channelId) {
            if (isset($this->feeders[$channelId])) {
                $this->feeders[$channelId]->signal(true);
            }
            if (!isset($this->updaters[$channelId])) {
                $this->updaters[$channelId]->signal(true);
            }
        }
        $this->channels_state->__construct($newStates);
        $this->startUpdateSystem();
    }

    public function startUpdateSystem($anyway = false)
    {
        if ($this->asyncInitPromise && !$anyway) {
            $this->logger("Not starting update system");
            return;
        }
        $this->logger("Starting update system");

        if (!isset($this->seqUpdater)) {
            $this->seqUpdater = new SeqLoop($this);
        }
        $this->channels_state->get(false);
        $channelIds = [];
        foreach ($this->channels_state->get() as $state) {
            $channelIds[] = $state->getChannel();
        }
        \sort($channelIds);
        foreach ($channelIds as $channelId) {
            if (!isset($this->feeders[$channelId])) {
                $this->feeders[$channelId] = new FeedLoop($this, $channelId);
            }
            if (!isset($this->updaters[$channelId])) {
                $this->updaters[$channelId] = new UpdateLoop($this, $channelId);
            }
            if ($this->feeders[$channelId]->start() && isset($this->feeders[$channelId])) {
                $this->feeders[$channelId]->resume();
            }
            if ($this->updaters[$channelId]->start() && isset($this->updaters[$channelId])) {
                $this->updaters[$channelId]->resume();
            }
        }
        foreach ($this->datacenter->getDataCenterConnections() as $datacenter) {
            $datacenter->flush();
        }
        if ($this->seqUpdater->start()) {
            $this->seqUpdater->resume();
        }
    }

    public function get_phone_config_async($watcherId = null)
    {
        if ($this->authorized === self::LOGGED_IN && \class_exists(VoIPServerConfigInternal::class) && !$this->authorization['user']['bot'] && $this->datacenter->getDataCenterConnection($this->settings['connection_settings']['default_dc'])->hasTempAuthKey()) {
            $this->logger->logger('Fetching phone config...');
            VoIPServerConfig::updateDefault(yield $this->method_call_async_read('phone.getCallConfig', [], ['datacenter' => $this->settings['connection_settings']['default_dc']]));
        } else {
            $this->logger->logger('Not fetching phone config');
        }
    }


    public function get_cdn_config_async($datacenter)
    {
        /*
         * ***********************************************************************
         * Fetch RSA keys for CDN datacenters
         */
        try {
            foreach ((yield $this->method_call_async_read('help.getCdnConfig', [], ['datacenter' => $datacenter]))['public_keys'] as $curkey) {
                $tempkey = new \danog\MadelineProto\RSA($curkey['public_key']);
                $this->cdn_rsa_keys[$tempkey->fp] = $tempkey;
            }
        } catch (\danog\MadelineProto\TL\Exception $e) {
            $this->logger->logger($e->getMessage(), \danog\MadelineProto\Logger::FATAL_ERROR);
        }
    }

    public function get_cached_config()
    {
        return $this->config;
    }

    public function get_config_async($config = [], $options = [])
    {
        if ($this->config['expires'] > \time()) {
            return $this->config;
        }
        $this->config = empty($config) ? yield $this->method_call_async_read('help.getConfig', $config, empty($options) ? ['datacenter' => $this->settings['connection_settings']['default_dc']] : $options) : $config;
        yield $this->parse_config_async();

        return $this->config;
    }

    public function parse_config_async()
    {
        if (isset($this->config['dc_options'])) {
            $options = $this->config['dc_options'];
            unset($this->config['dc_options']);
            yield $this->parse_dc_options_async($options);
        }
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['config_updated'], Logger::NOTICE);
        $this->logger->logger($this->config, Logger::NOTICE);
    }

    public function parse_dc_options_async($dc_options)
    {
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
            if (\is_numeric($id)) {
                $id = (int) $id;
            }
            unset($dc['cdn'], $dc['media_only'], $dc['id'], $dc['ipv6']);

            $this->settings['connection'][$test][$ipv6][$id] = $dc;
        }
        $curdc = $this->datacenter->curdc;
        if (!$this->datacenter->has($curdc) || $this->datacenter->getDataCenterConnection($curdc)->byIPAddress()) {
            $this->logger->logger('Got new DC options, reconnecting');
            yield $this->connect_to_all_dcs_async(false);
        }
        $this->datacenter->curdc = $curdc;
    }

    public function get_self_async()
    {
        try {
            $this->authorization = ['user' => (yield $this->method_call_async_read('users.getUsers', ['id' => [['_' => 'inputUserSelf']]], ['datacenter' => $this->datacenter->curdc]))[0]];
        } catch (RPCErrorException $e) {
            $this->logger->logger($e->getMessage());

            return false;
        }

        return $this->authorization['user'];
    }

    public function getMethodCallbacks(): array
    {
        return [];
    }

    public function getMethodBeforeCallbacks(): array
    {
        return [];
    }

    public function getConstructorCallbacks(): array
    {
        return \array_merge(
            \array_fill_keys(['chat', 'chatEmpty', 'chatForbidden', 'channel', 'channelEmpty', 'channelForbidden'], [[$this, 'add_chat_async']]),
            \array_fill_keys(['user', 'userEmpty'], [[$this, 'add_user']]),
            ['help.support' => [[$this, 'add_support']]]
        );
    }

    public function getConstructorBeforeCallbacks(): array
    {
        return [];
    }

    public function getConstructorSerializeCallbacks(): array
    {
        return [];
    }

    public function getTypeMismatchCallbacks(): array
    {
        return \array_merge(
            \array_fill_keys(['User', 'InputUser', 'Chat', 'InputChannel', 'Peer', 'InputPeer', 'InputDialogPeer', 'InputNotifyPeer'], [$this, 'get_info_async']),
            \array_fill_keys(['InputMedia', 'InputDocument', 'InputPhoto'], [$this, 'get_file_info_async']),
            \array_fill_keys(['InputFileLocation'], [$this, 'get_download_info_async'])
        );
    }


    public function __debugInfo()
    {
        return ['MadelineProto instance '.\spl_object_hash($this)];
    }

    const ALL_MIMES = ['webp' => [0 => 'image/webp'], 'png' => [0 => 'image/png', 1 => 'image/x-png'], 'bmp' => [0 => 'image/bmp', 1 => 'image/x-bmp', 2 => 'image/x-bitmap', 3 => 'image/x-xbitmap', 4 => 'image/x-win-bitmap', 5 => 'image/x-windows-bmp', 6 => 'image/ms-bmp', 7 => 'image/x-ms-bmp', 8 => 'application/bmp', 9 => 'application/x-bmp', 10 => 'application/x-win-bitmap'], 'gif' => [0 => 'image/gif'], 'jpeg' => [0 => 'image/jpeg', 1 => 'image/pjpeg'], 'xspf' => [0 => 'application/xspf+xml'], 'vlc' => [0 => 'application/videolan'], 'wmv' => [0 => 'video/x-ms-wmv', 1 => 'video/x-ms-asf'], 'au' => [0 => 'audio/x-au'], 'ac3' => [0 => 'audio/ac3'], 'flac' => [0 => 'audio/x-flac'], 'ogg' => [0 => 'audio/ogg', 1 => 'video/ogg', 2 => 'application/ogg'], 'kmz' => [0 => 'application/vnd.google-earth.kmz'], 'kml' => [0 => 'application/vnd.google-earth.kml+xml'], 'rtx' => [0 => 'text/richtext'], 'rtf' => [0 => 'text/rtf'], 'jar' => [0 => 'application/java-archive', 1 => 'application/x-java-application', 2 => 'application/x-jar'], 'zip' => [0 => 'application/x-zip', 1 => 'application/zip', 2 => 'application/x-zip-compressed', 3 => 'application/s-compressed', 4 => 'multipart/x-zip'], '7zip' => [0 => 'application/x-compressed'], 'xml' => [0 => 'application/xml', 1 => 'text/xml'], 'svg' => [0 => 'image/svg+xml'], '3g2' => [0 => 'video/3gpp2'], '3gp' => [0 => 'video/3gp', 1 => 'video/3gpp'], 'mp4' => [0 => 'video/mp4'], 'm4a' => [0 => 'audio/x-m4a'], 'f4v' => [0 => 'video/x-f4v'], 'flv' => [0 => 'video/x-flv'], 'webm' => [0 => 'video/webm'], 'aac' => [0 => 'audio/x-acc'], 'm4u' => [0 => 'application/vnd.mpegurl'], 'pdf' => [0 => 'application/pdf', 1 => 'application/octet-stream'], 'pptx' => [0 => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'], 'ppt' => [0 => 'application/powerpoint', 1 => 'application/vnd.ms-powerpoint', 2 => 'application/vnd.ms-office', 3 => 'application/msword'], 'docx' => [0 => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], 'xlsx' => [0 => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 1 => 'application/vnd.ms-excel'], 'xl' => [0 => 'application/excel'], 'xls' => [0 => 'application/msexcel', 1 => 'application/x-msexcel', 2 => 'application/x-ms-excel', 3 => 'application/x-excel', 4 => 'application/x-dos_ms_excel', 5 => 'application/xls', 6 => 'application/x-xls'], 'xsl' => [0 => 'text/xsl'], 'mpeg' => [0 => 'video/mpeg'], 'mov' => [0 => 'video/quicktime'], 'avi' => [0 => 'video/x-msvideo', 1 => 'video/msvideo', 2 => 'video/avi', 3 => 'application/x-troff-msvideo'], 'movie' => [0 => 'video/x-sgi-movie'], 'log' => [0 => 'text/x-log'], 'txt' => [0 => 'text/plain'], 'css' => [0 => 'text/css'], 'html' => [0 => 'text/html'], 'wav' => [0 => 'audio/x-wav', 1 => 'audio/wave', 2 => 'audio/wav'], 'xhtml' => [0 => 'application/xhtml+xml'], 'tar' => [0 => 'application/x-tar'], 'tgz' => [0 => 'application/x-gzip-compressed'], 'psd' => [0 => 'application/x-photoshop', 1 => 'image/vnd.adobe.photoshop'], 'exe' => [0 => 'application/x-msdownload'], 'js' => [0 => 'application/x-javascript'], 'mp3' => [0 => 'audio/mpeg', 1 => 'audio/mpg', 2 => 'audio/mpeg3', 3 => 'audio/mp3'], 'rar' => [0 => 'application/x-rar', 1 => 'application/rar', 2 => 'application/x-rar-compressed'], 'gzip' => [0 => 'application/x-gzip'], 'hqx' => [0 => 'application/mac-binhex40', 1 => 'application/mac-binhex', 2 => 'application/x-binhex40', 3 => 'application/x-mac-binhex40'], 'cpt' => [0 => 'application/mac-compactpro'], 'bin' => [0 => 'application/macbinary', 1 => 'application/mac-binary', 2 => 'application/x-binary', 3 => 'application/x-macbinary'], 'oda' => [0 => 'application/oda'], 'ai' => [0 => 'application/postscript'], 'smil' => [0 => 'application/smil'], 'mif' => [0 => 'application/vnd.mif'], 'wbxml' => [0 => 'application/wbxml'], 'wmlc' => [0 => 'application/wmlc'], 'dcr' => [0 => 'application/x-director'], 'dvi' => [0 => 'application/x-dvi'], 'gtar' => [0 => 'application/x-gtar'], 'php' => [0 => 'application/x-httpd-php', 1 => 'application/php', 2 => 'application/x-php', 3 => 'text/php', 4 => 'text/x-php', 5 => 'application/x-httpd-php-source'], 'swf' => [0 => 'application/x-shockwave-flash'], 'sit' => [0 => 'application/x-stuffit'], 'z' => [0 => 'application/x-compress'], 'mid' => [0 => 'audio/midi'], 'aif' => [0 => 'audio/x-aiff', 1 => 'audio/aiff'], 'ram' => [0 => 'audio/x-pn-realaudio'], 'rpm' => [0 => 'audio/x-pn-realaudio-plugin'], 'ra' => [0 => 'audio/x-realaudio'], 'rv' => [0 => 'video/vnd.rn-realvideo'], 'jp2' => [0 => 'image/jp2', 1 => 'video/mj2', 2 => 'image/jpx', 3 => 'image/jpm'], 'tiff' => [0 => 'image/tiff'], 'eml' => [0 => 'message/rfc822'], 'pem' => [0 => 'application/x-x509-user-cert', 1 => 'application/x-pem-file'], 'p10' => [0 => 'application/x-pkcs10', 1 => 'application/pkcs10'], 'p12' => [0 => 'application/x-pkcs12'], 'p7a' => [0 => 'application/x-pkcs7-signature'], 'p7c' => [0 => 'application/pkcs7-mime', 1 => 'application/x-pkcs7-mime'], 'p7r' => [0 => 'application/x-pkcs7-certreqresp'], 'p7s' => [0 => 'application/pkcs7-signature'], 'crt' => [0 => 'application/x-x509-ca-cert', 1 => 'application/pkix-cert'], 'crl' => [0 => 'application/pkix-crl', 1 => 'application/pkcs-crl'], 'pgp' => [0 => 'application/pgp'], 'gpg' => [0 => 'application/gpg-keys'], 'rsa' => [0 => 'application/x-pkcs7'], 'ics' => [0 => 'text/calendar'], 'zsh' => [0 => 'text/x-scriptzsh'], 'cdr' => [0 => 'application/cdr', 1 => 'application/coreldraw', 2 => 'application/x-cdr', 3 => 'application/x-coreldraw', 4 => 'image/cdr', 5 => 'image/x-cdr', 6 => 'zz-application/zz-winassoc-cdr'], 'wma' => [0 => 'audio/x-ms-wma'], 'vcf' => [0 => 'text/x-vcard'], 'srt' => [0 => 'text/srt'], 'vtt' => [0 => 'text/vtt'], 'ico' => [0 => 'image/x-icon', 1 => 'image/x-ico', 2 => 'image/vnd.microsoft.icon'], 'csv' => [0 => 'text/x-comma-separated-values', 1 => 'text/comma-separated-values', 2 => 'application/vnd.msexcel'], 'json' => [0 => 'application/json', 1 => 'text/json']];
}
