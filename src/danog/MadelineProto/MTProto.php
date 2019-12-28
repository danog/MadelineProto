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
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Dns\Resolver;
use Amp\Http\Client\DelegateHttpClient;
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
use danog\MadelineProto\TL\TL;
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
    const DISALLOWED_METHODS = ['account.updatePasswordSettings' => 'You cannot use this method directly; use $MadelineProto->update_2fa($params), instead (see https://docs.madelineproto.xyz for more info)', 'account.getPasswordSettings' => 'You cannot use this method directly; use $MadelineProto->update_2fa($params), instead (see https://docs.madelineproto.xyz for more info)', 'messages.receivedQueue' => 'You cannot use this method directly', 'messages.getDhConfig' => 'You cannot use this method directly, instead use $MadelineProto->getDhConfig();', 'auth.bindTempAuthKey' => 'You cannot use this method directly, instead modify the PFS and default_temp_auth_key_expires_in settings, see https://docs.madelineproto.xyz/docs/SETTINGS.html for more info', 'auth.exportAuthorization' => 'You cannot use this method directly, use $MadelineProto->exportAuthorization() instead, see https://docs.madelineproto.xyz/docs/LOGIN.html', 'auth.importAuthorization' => 'You cannot use this method directly, use $MadelineProto->importAuthorization($authorization) instead, see https://docs.madelineproto.xyz/docs/LOGIN.html', 'auth.logOut' => 'You cannot use this method directly, use the logout method instead (see https://docs.madelineproto.xyz for more info)', 'auth.importBotAuthorization' => 'You cannot use this method directly, use the botLogin method instead (see https://docs.madelineproto.xyz for more info)', 'auth.sendCode' => 'You cannot use this method directly, use the phoneLogin method instead (see https://docs.madelineproto.xyz for more info)', 'auth.signIn' => 'You cannot use this method directly, use the completePhoneLogin method instead (see https://docs.madelineproto.xyz for more info)', 'auth.checkPassword' => 'You cannot use this method directly, use the complete_2fa_login method instead (see https://docs.madelineproto.xyz for more info)', 'auth.signUp' => 'You cannot use this method directly, use the completeSignup method instead (see https://docs.madelineproto.xyz for more info)', 'users.getFullUser' => 'You cannot use this method directly, use the getPwrChat, getInfo, getFullInfo methods instead (see https://docs.madelineproto.xyz for more info)', 'channels.getFullChannel' => 'You cannot use this method directly, use the getPwrChat, getInfo, getFullInfo methods instead (see https://docs.madelineproto.xyz for more info)', 'messages.getFullChat' => 'You cannot use this method directly, use the getPwrChat, getInfo, getFullInfo methods instead (see https://docs.madelineproto.xyz for more info)', 'contacts.resolveUsername' => 'You cannot use this method directly, use the resolveUsername, getPwrChat, getInfo, getFullInfo methods instead (see https://docs.madelineproto.xyz for more info)', 'messages.acceptEncryption' => 'You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling secret chats', 'messages.discardEncryption' => 'You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling secret chats', 'messages.requestEncryption' => 'You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling secret chats', 'phone.requestCall' => 'You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls', 'phone.acceptCall' => 'You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls', 'phone.confirmCall' => 'You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls', 'phone.discardCall' => 'You cannot use this method directly, see https://docs.madelineproto.xyz#calls for more info on handling calls', 'updates.getChannelDifference' => 'You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling updates', 'updates.getDifference' => 'You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling updates', 'updates.getState' => 'You cannot use this method directly, see https://docs.madelineproto.xyz for more info on handling updates', 'upload.getCdnFile' => 'You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info', 'upload.getFileHashes' => 'You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info', 'upload.getCdnFileHashes' => 'You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info', 'upload.reuploadCdnFile' => 'You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info', 'upload.getFile' => 'You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info', 'upload.saveFilePart' => 'You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info', 'upload.saveBigFilePart' => 'You cannot use this method directly, use the upload, downloadToStream, downloadToFile, downloadToDir methods instead; see https://docs.madelineproto.xyz for more info'];
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
    /**
     * Secret chat was not found.
     *
     * @var int
     */
    const SECRET_EMPTY = 0;
    /**
     * Secret chat was requested.
     *
     * @var int
     */
    const SECRET_REQUESTED = 1;
    /**
     * Secret chat was found.
     *
     * @var int
     */
    const SECRET_READY = 2;
    const TD_PARAMS_CONVERSION = ['updateNewMessage' => ['_' => 'updateNewMessage', 'disable_notification' => ['message', 'silent'], 'message' => ['message']], 'message' => ['_' => 'message', 'id' => ['id'], 'sender_user_id' => ['from_id'], 'chat_id' => ['to_id', 'choose_chat_id_from_botapi'], 'send_state' => ['choose_incoming_or_sent'], 'can_be_edited' => ['choose_can_edit'], 'can_be_deleted' => ['choose_can_delete'], 'is_post' => ['post'], 'date' => ['date'], 'edit_date' => ['edit_date'], 'forward_info' => ['fwd_info', 'choose_forward_info'], 'reply_to_message_id' => ['reply_to_msg_id'], 'ttl' => ['choose_ttl'], 'ttl_expires_in' => ['choose_ttl_expires_in'], 'via_bot_user_id' => ['via_bot_id'], 'views' => ['views'], 'content' => ['choose_message_content'], 'reply_markup' => ['reply_markup']], 'messages.sendMessage' => ['chat_id' => ['peer'], 'reply_to_message_id' => ['reply_to_msg_id'], 'disable_notification' => ['silent'], 'from_background' => ['background'], 'input_message_content' => ['choose_message_content'], 'reply_markup' => ['reply_markup']]];
    const TD_REVERSE = ['sendMessage' => 'messages.sendMessage'];
    const TD_IGNORE = ['updateMessageID'];
    const BOTAPI_PARAMS_CONVERSION = ['disable_web_page_preview' => 'no_webpage', 'disable_notification' => 'silent', 'reply_to_message_id' => 'reply_to_msg_id', 'chat_id' => 'peer', 'text' => 'message'];
    // Not content related constructors
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
     * @var array|null
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
     * Logger instance.
     *
     * @var Logger
     */
    public $logger;

    /**
     * TL serializer.
     *
     * @var \danog\MadelineProto\TL\TL
     */
    private $TL;

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
        Magic::classExists();
        // Parse and store settings
        $this->parseSettings($settings);
        $this->logger->logger(Lang::$current_lang['inst_dc'], Logger::ULTRA_VERBOSE);
        $this->cleanupProperties();
        // Load rsa keys
        $this->logger->logger(Lang::$current_lang['load_rsa'], Logger::ULTRA_VERBOSE);
        $this->rsa_keys = [];
        foreach ($this->settings['authorization']['rsa_keys'] as $key) {
            $key = yield (new RSA())->load($this->TL, $key);
            $this->rsa_keys[$key->fp] = $key;
        }
        // (re)-initialize TL
        $this->logger->logger(Lang::$current_lang['TL_translation'], Logger::ULTRA_VERBOSE);
        $callbacks = [$this, $this->referenceDatabase];
        if (!($this->authorization['user']['bot'] ?? false)) {
            $callbacks []= $this->minDatabase;
        }
        $this->TL->init($this->settings['tl_schema']['src'], $callbacks);

        yield $this->connectToAllDcs();
        $this->startLoops();
        $this->datacenter->curdc = 2;
        if ((!isset($this->authorization['user']['bot']) || !$this->authorization['user']['bot']) && $this->datacenter->getDataCenterConnection($this->datacenter->curdc)->hasTempAuthKey()) {
            try {
                $nearest_dc = yield $this->methodCallAsyncRead('help.getNearestDc', [], ['datacenter' => $this->datacenter->curdc]);
                $this->logger->logger(\sprintf(Lang::$current_lang['nearest_dc'], $nearest_dc['country'], $nearest_dc['nearest_dc']), Logger::NOTICE);
                if ($nearest_dc['nearest_dc'] != $nearest_dc['this_dc']) {
                    $this->settings['connection_settings']['default_dc'] = $this->datacenter->curdc = (int) $nearest_dc['nearest_dc'];
                }
            } catch (RPCErrorException $e) {
                if ($e->rpc !== 'BOT_METHOD_INVALID') {
                    throw $e;
                }
            }
        }
        yield $this->getConfig([], ['datacenter' => $this->datacenter->curdc]);
        $this->startUpdateSystem(true);
        $this->v = self::V;
    }

    /**
     * Sleep function.
     *
     * @return array
     */
    public function __sleep(): array
    {
        if ($this->settings['serialization']['cleanup_before_serialization']) {
            $this->cleanup();
        }
        return [
            // Databases
            'chats',
            'full_chats',
            'referenceDatabase',
            'minDatabase',
            'channel_participants',

            // Misc caching
            'dialog_params',
            'last_stored',
            'qres',
            'supportUser',
            'tos',

            // Event handler
            'event_handler',
            'event_handler_instance',
            'loop_callback',
            'updates',
            'updates_key',

            'hook_url',

            // Web login template
            'web_template',

            // Settings
            'settings',
            'config',

            // Authorization keys
            'datacenter',

            // Authorization state
            'authorization',
            'authorized',
            'authorized_dc',

            // Authorization cache
            'rsa_keys',
            'dh_config',

            // Update state
            'got_state',
            'channels_state',
            'msg_ids',

            // Version
            'v',

            // TL
            'TL',

            // Secret chats
            'secret_chats',
            'temp_requested_secret_chats',
            'temp_rekeyed_secret_chats',

            // Object storage
            'storage',
        ];
    }


    /**
     * Cleanup memory and session file.
     *
     * @return self
     */
    public function cleanup(): self
    {
        $this->referenceDatabase = new ReferenceDatabase($this);
        $callbacks = [$this, $this->referenceDatabase];
        if (!($this->authorization['user']['bot'] ?? false)) {
            $callbacks []= $this->minDatabase;
        }
        $this->TL->updateCallbacks($callbacks);
        return $this;
    }

    /**
     * Logger.
     *
     * @param string $param Parameter
     * @param int    $level Logging level
     * @param string $file  File where the message originated
     *
     * @return void
     */
    public function logger($param, int $level = Logger::NOTICE, string $file = ''): void
    {
        if ($file === null) {
            $file = \basename(\debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'], '.php');
        }

        isset($this->logger) ? $this->logger->logger($param, $level, $file) : Logger::$default->logger($param, $level, $file);
    }

    /**
     * Get TL namespaces.
     *
     * @return array
     */
    public function getMethodNamespaces(): array
    {
        return $this->TL->getMethodNamespaces();
    }


    /**
     * Get namespaced methods (method => namespace).
     *
     * @return array
     */
    public function getMethodsNamespaced(): array
    {
        return $this->TL->getMethodsNamespaced();
    }

    /**
     * Get TL serializer.
     *
     * @return TL
     */
    public function getTL(): \danog\MadelineProto\TL\TL
    {
        return $this->TL;
    }
    /**
     * Get logger.
     *
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }

    /**
     * Get async HTTP client.
     *
     * @return \Amp\Http\Client\DelegateHttpClient
     */
    public function getHTTPClient(): DelegateHttpClient
    {
        return $this->datacenter->getHTTPClient();
    }

    /**
     * Get async DNS client.
     *
     * @return \Amp\Dns\Resolver
     */
    public function getDNSClient(): Resolver
    {
        return $this->datacenter->getDNSClient();
    }

    /**
     * Get contents of remote file asynchronously.
     *
     * @param string $url URL
     *
     * @return \Generator<string>
     */
    public function fileGetContents(string $url): \Generator
    {
        return $this->datacenter->fileGetContents($url);
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

    /**
     * Prompt serialization of instance.
     *
     * @return void
     */
    public function serialize()
    {
        if ($this->wrapper instanceof API && isset($this->wrapper->session) && !\is_null($this->wrapper->session) && !$this->asyncInitPromise) {
            //$this->logger->logger("Didn't serialize in a while, doing that now...");
            $this->wrapper->serialize($this->wrapper->session);
        }
    }
    /**
     * Start all internal loops.
     *
     * @return void
     */
    private function startLoops()
    {
        if (!$this->callCheckerLoop) {
            $this->callCheckerLoop = new PeriodicLoop($this, [$this, 'checkCalls'], 'call check', 10);
        }
        if (!$this->serializeLoop) {
            $this->serializeLoop = new PeriodicLoop($this, [$this, 'serialize'], 'serialize', $this->settings['serialization']['serialization_interval']);
        }
        if (!$this->phoneConfigLoop) {
            $this->phoneConfigLoop = new PeriodicLoop($this, [$this, 'getPhoneConfig'], 'phone config', 24 * 3600);
        }
        if (!$this->checkTosLoop) {
            $this->checkTosLoop = new PeriodicLoop($this, [$this, 'checkTos'], 'TOS', 24 * 3600);
        }
        if (!$this->configLoop) {
            $this->configLoop = new PeriodicLoop($this, [$this, 'getConfig'], 'config', 24 * 3600);
        }

        $this->callCheckerLoop->start();
        $this->serializeLoop->start();
        $this->phoneConfigLoop->start();
        $this->configLoop->start();
        $this->checkTosLoop->start();
    }
    /**
     * Stop all internal loops.
     *
     * @return void
     */
    private function stopLoops()
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

    /**
     * Clean up properties from previous versions of MadelineProto.
     *
     * @internal
     *
     * @return void
     */
    private function cleanupProperties()
    {
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
        if (!isset($this->TL)) {
            $this->TL = new TL($this);
            $this->logger->logger(Lang::$current_lang['TL_translation'], Logger::ULTRA_VERBOSE);
            $callbacks = [$this, $this->referenceDatabase];
            if (!($this->authorization['user']['bot'] ?? false)) {
                $callbacks []= $this->minDatabase;
            }
            $this->TL->init($this->settings['tl_schema']['src'], $callbacks);
        }
    }
    /**
     * Upgrade MadelineProto instance.
     *
     * @return \Generator
     */
    private function upgradeMadelineProto(): \Generator
    {
        $this->logger->logger(Lang::$current_lang['serialization_ofd'], Logger::WARNING);
        foreach ($this->datacenter->getDataCenterConnections() as $dc_id => $socket) {
            if ($this->authorized === self::LOGGED_IN && \strpos($dc_id, '_') === false && $socket->hasPermAuthKey() && $socket->hasTempAuthKey()) {
                $socket->bind();
                $socket->authorized(true);
            }
        }
        $settings = $this->settings;
        if (isset($settings['updates']['callback'][0]) && $settings['updates']['callback'][0] === $this) {
            $settings['updates']['callback'] = 'getUpdatesUpdateHandler';
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
        foreach ($this->secret_chats as $chat => $data) {
            try {
                if (isset($this->secret_chats[$chat]) && $this->secret_chats[$chat]['InputEncryptedChat'] !== null) {
                    yield $this->notifyLayer($chat);
                }
            } catch (\danog\MadelineProto\RPCErrorException $e) {
            }
        }
    }
    /**
     * Wakeup function.
     */
    public function __wakeup()
    {
        $backtrace = \debug_backtrace(0, 3);
        $this->asyncInitPromise = true;
        $this->setInitPromise($this->__wakeup_async($backtrace));
    }

    /**
     * Async wakeup function.
     *
     * @param array $backtrace Stack trace
     *
     * @return \Generator
     */
    public function __wakeup_async(array $backtrace): \Generator
    {
        // Setup one-time stuffs
        Magic::classExists();
        // Setup logger
        $this->setupLogger();
        // We don't like threads
        if (Magic::$has_thread && \is_object(\Thread::getCurrentThread())) {
            return;
        }
        // Setup language
        Lang::$current_lang = &Lang::$lang['en'];
        if (Lang::$lang[$this->settings['app_info']['lang_code'] ?? 'en'] ?? false) {
            Lang::$current_lang = &Lang::$lang[$this->settings['app_info']['lang_code']];
        }

        $this->settings['connection_settings']['all']['ipv6'] = Magic::$ipv6;
        if ($this->authorized === true) {
            $this->authorized = self::LOGGED_IN;
        }
        $force = false;
        $this->resetMTProtoSession();
        if (isset($backtrace[2]['function'], $backtrace[2]['class'], $backtrace[2]['args']) && $backtrace[2]['class'] === 'danog\\MadelineProto\\API' && $backtrace[2]['function'] === '__construct_async') {
            if (\count($backtrace[2]['args']) >= 2) {
                $this->parseSettings(\array_replace_recursive($this->settings, $backtrace[2]['args'][1]));
            }
        }

        if (($this->settings['tl_schema']['src']['botAPI'] ?? '') !== __DIR__.'/TL_botAPI.tl') {
            unset($this->v);
        }
        if (!\file_exists($this->settings['tl_schema']['src']['telegram'])) {
            unset($this->v);
        }

        if (!isset($this->v) || $this->v !== self::V) {
            yield $this->upgradeMadelineProto();
            $force = true;
        }

        // Cleanup old properties, init new stuffs
        $this->cleanupProperties();

        // Update TL callbacks
        $callbacks = [$this, $this->referenceDatabase];
        if (!($this->authorization['user']['bot'] ?? false)) {
            $callbacks []= $this->minDatabase;
        }
        $this->TL->updateCallbacks($callbacks);

        if ($this->event_handler && \class_exists($this->event_handler) && \is_subclass_of($this->event_handler, EventHandler::class)) {
            $this->setEventHandler($this->event_handler);
        }

        yield $this->connectToAllDcs();
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
        if (yield $this->getSelf()) {
            $this->authorized = self::LOGGED_IN;
        }

        if ($this->authorized === self::LOGGED_IN) {
            yield $this->getCdnConfig($this->datacenter->curdc);
            $this->setupLogger();
        }
        $this->startUpdateSystem(true);
        if ($this->authorized === self::LOGGED_IN && !$this->authorization['user']['bot'] && $this->settings['peer']['cache_all_peers_on_startup']) {
            yield $this->getDialogs($force);
        }
        if ($this->authorized === self::LOGGED_IN && $this->settings['updates']['handle_updates']) {
            $this->logger->logger(Lang::$current_lang['getupdates_deserialization'], Logger::NOTICE);
            yield $this->updaters[false]->resume();
        }
        $this->updaters[false]->start();
    }

    /**
     * Destructor.
     */
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

    /**
     * Get correct settings array for the latest version.
     *
     * @param array $settings         Current settings array
     * @param array $previousSettings Previous settings array
     *
     * @return array
     */
    public static function getSettings(array $settings, array $previousSettings = []): array
    {
        Magic::classExists();
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
                'ipv6' => Magic::$ipv6,
                // decides whether to use ipv6, ipv6 attribute of API attribute of API class contains autodetected boolean
                'timeout' => 2,
                // timeout for sockets
                'proxy' => Magic::$altervista ? '\\HttpProxy' : '\\Socket',
                // The proxy class to use
                'proxy_extra' => Magic::$altervista ? ['address' => 'localhost', 'port' => 80] : [],
                // Extra parameters to pass to the proxy class using setExtra
                'obfuscated' => false,
                'transport' => 'tcp',
                'pfs' => false,//\extension_loaded('gmp'),
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
            'callback' => 'getUpdatesUpdateHandler',
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
    /**
     * Parse and store settings.
     *
     * @param array $settings Settings
     *
     * @return void
     */
    private function parseSettings(array $settings): void
    {
        $settings = self::getSettings($settings, $this->settings);
        if ($settings['app_info'] === null) {
            throw new \danog\MadelineProto\Exception(Lang::$current_lang['api_not_set'], 0, null, 'MadelineProto', 1);
        }
        $this->settings = $settings;
        if (!$this->settings['updates']['handle_updates']) {
            $this->updates = [];
        }
        // Setup logger
        $this->setupLogger();
    }

    /**
     * Setup logger.
     *
     * @return void
     */
    public function setupLogger(): void
    {
        $this->logger = Logger::getLoggerFromSettings($this->settings, isset($this->authorization['user']) ? isset($this->authorization['user']['username']) ? $this->authorization['user']['username'] : $this->authorization['user']['id'] : '');
    }

    /**
     * Reset all MTProto sessions.
     *
     * @param boolean $de       Whether to reset the session ID
     * @param boolean $auth_key Whether to reset the auth key
     *
     * @internal
     *
     * @return void
     */
    public function resetMTProtoSession(bool $de = true, bool $auth_key = false): void
    {
        if (!\is_object($this->datacenter)) {
            throw new Exception(Lang::$current_lang['session_corrupted']);
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
     * @internal
     *
     * @return boolean
     */
    public function isHttp(string $datacenter): bool
    {
        return $this->datacenter->isHttp($datacenter);
    }

    /**
     * Checks whether all datacenters are authorized.
     *
     * @return boolean
     */
    public function hasAllAuth(): bool
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

    /**
     * Whether we're initing authorization.
     *
     * @internal
     *
     * @return boolean
     */
    public function isInitingAuthorization()
    {
        return $this->initing_authorization;
    }

    /**
     * Connects to all datacenters and if necessary creates authorization keys, binds them and writes client info.
     *
     * @param boolean $reconnectAll Whether to reconnect to all DCs
     *
     * @return \Generator
     */
    public function connectToAllDcs(bool $reconnectAll = true): \Generator
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
        foreach ($this->datacenter->getDcs() as $new_dc) {
            $dcs[] = $this->datacenter->dcConnect($new_dc);
        }
        yield \danog\MadelineProto\Tools::all($dcs);
        yield $this->initAuthorization();
        yield $this->parseConfig();
        $dcs = [];
        foreach ($this->datacenter->getDcs(false) as $new_dc) {
            $dcs[] = $this->datacenter->dcConnect($new_dc);
        }
        yield \danog\MadelineProto\Tools::all($dcs);
        yield $this->initAuthorization();
        yield $this->parseConfig();

        yield $this->getPhoneConfig();
    }
    /**
     * Clean up MadelineProto session after logout.
     *
     * @internal
     *
     * @return void
     */
    public function resetSession(): void
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
    /**
     * Reset the update state and fetch all updates from the beginning.
     *
     * @return void
     */
    public function resetUpdateState(): void
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

    /**
     * Start the update system.
     *
     * @param boolean $anyway Force start update system?
     *
     * @internal
     *
     * @return void
     */
    public function startUpdateSystem($anyway = false): void
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

    /**
     * Store shared phone config.
     *
     * @param mixed $watcherId Watcher ID
     *
     * @internal
     *
     * @return \Generator<void>
     */
    public function getPhoneConfig($watcherId = null): \Generator
    {
        if ($this->authorized === self::LOGGED_IN && \class_exists(VoIPServerConfigInternal::class) && !$this->authorization['user']['bot'] && $this->datacenter->getDataCenterConnection($this->settings['connection_settings']['default_dc'])->hasTempAuthKey()) {
            $this->logger->logger('Fetching phone config...');
            VoIPServerConfig::updateDefault(yield $this->methodCallAsyncRead('phone.getCallConfig', [], ['datacenter' => $this->settings['connection_settings']['default_dc']]));
        } else {
            $this->logger->logger('Not fetching phone config');
        }
    }

    /**
     * Store RSA keys for CDN datacenters.
     *
     * @param string $datacenter DC ID
     *
     * @return \Generator
     */
    public function getCdnConfig(string $datacenter): \Generator
    {
        try {
            foreach ((yield $this->methodCallAsyncRead('help.getCdnConfig', [], ['datacenter' => $datacenter]))['public_keys'] as $curkey) {
                $curkey = yield (new RSA)->load($this->TL, $curkey['public_key']);
                $this->cdn_rsa_keys[$curkey->fp] = $curkey;
            }
        } catch (\danog\MadelineProto\TL\Exception $e) {
            $this->logger->logger($e->getMessage(), \danog\MadelineProto\Logger::FATAL_ERROR);
        }
    }

    /**
     * Get cached server-side config.
     *
     * @return array
     */
    public function getCachedConfig(): array
    {
        return $this->config;
    }

    /**
     * Get cached (or eventually re-fetch) server-side config.
     *
     * @param array $config  Current config
     * @param array $options Options for method call
     *
     * @return \Generator
     */
    public function getConfig(array $config = [], array $options = []): \Generator
    {
        if ($this->config['expires'] > \time()) {
            return $this->config;
        }
        $this->config = empty($config) ? yield $this->methodCallAsyncRead('help.getConfig', $config, empty($options) ? ['datacenter' => $this->settings['connection_settings']['default_dc']] : $options) : $config;
        yield $this->parseConfig();

        return $this->config;
    }

    /**
     * Parse cached config.
     *
     * @return \Generator
     */
    private function parseConfig(): \Generator
    {
        if (isset($this->config['dc_options'])) {
            $options = $this->config['dc_options'];
            unset($this->config['dc_options']);
            yield $this->parseDcOptions($options);
        }
        $this->logger->logger(Lang::$current_lang['config_updated'], Logger::NOTICE);
        $this->logger->logger($this->config, Logger::NOTICE);
    }

    /**
     * Parse DC options from config.
     *
     * @param array $dc_options DC options
     *
     * @return \Generator
     */
    private function parseDcOptions(array $dc_options): \Generator
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
            yield $this->connectToAllDcs(false);
        }
        $this->datacenter->curdc = $curdc;
    }

    /**
     * Get info about the logged-in user.
     *
     * @return \Generator<array>
     */
    public function getSelf(): \Generator
    {
        try {
            $this->authorization = ['user' => (yield $this->methodCallAsyncRead('users.getUsers', ['id' => [['_' => 'inputUserSelf']]], ['datacenter' => $this->datacenter->curdc]))[0]];
        } catch (RPCErrorException $e) {
            $this->logger->logger($e->getMessage());

            return false;
        }

        return $this->authorization['user'];
    }

    /**
     * Called right before serialization of method starts.
     *
     * Pass the method name
     *
     * @return array
     */
    public function getMethodCallbacks(): array
    {
        return [];
    }

    /**
     * Called right before serialization of method starts.
     *
     * Pass the method name
     *
     * @return array
     */
    public function getMethodBeforeCallbacks(): array
    {
        return [];
    }

    /**
     * Called right after deserialization of object, passing the final object.
     *
     * @return array
     */
    public function getConstructorCallbacks(): array
    {
        return \array_merge(
            \array_fill_keys(['chat', 'chatEmpty', 'chatForbidden', 'channel', 'channelEmpty', 'channelForbidden'], [[$this, 'addChat']]),
            \array_fill_keys(['user', 'userEmpty'], [[$this, 'addUser']]),
            ['help.support' => [[$this, 'addSupport']]]
        );
    }

    /**
     * Called right before deserialization of object.
     *
     * Pass only the constructor name
     *
     * @return array
     */
    public function getConstructorBeforeCallbacks(): array
    {
        return [];
    }

    /**
     * Called right before serialization of constructor.
     *
     * Passed the object, will return a modified version.
     *
     * @return array
     */
    public function getConstructorSerializeCallbacks(): array
    {
        return [];
    }

    /**
     * Called if objects of the specified type cannot be serialized.
     *
     * Passed the unserializable object,
     * will try to convert it to an object of the proper type.
     *
     * @return array
     */
    public function getTypeMismatchCallbacks(): array
    {
        return \array_merge(
            \array_fill_keys(['User', 'InputUser', 'Chat', 'InputChannel', 'Peer', 'InputPeer', 'InputDialogPeer', 'InputNotifyPeer'], [$this, 'getInfo']),
            \array_fill_keys(['InputMedia', 'InputDocument', 'InputPhoto'], [$this, 'getFileInfo']),
            \array_fill_keys(['InputFileLocation'], [$this, 'getDownloadInfo'])
        );
    }


    /**
     * Get debug information for var_dump.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        return ['MadelineProto instance '.\spl_object_hash($this)];
    }

    const ALL_MIMES = ['webp' => [0 => 'image/webp'], 'png' => [0 => 'image/png', 1 => 'image/x-png'], 'bmp' => [0 => 'image/bmp', 1 => 'image/x-bmp', 2 => 'image/x-bitmap', 3 => 'image/x-xbitmap', 4 => 'image/x-win-bitmap', 5 => 'image/x-windows-bmp', 6 => 'image/ms-bmp', 7 => 'image/x-ms-bmp', 8 => 'application/bmp', 9 => 'application/x-bmp', 10 => 'application/x-win-bitmap'], 'gif' => [0 => 'image/gif'], 'jpeg' => [0 => 'image/jpeg', 1 => 'image/pjpeg'], 'xspf' => [0 => 'application/xspf+xml'], 'vlc' => [0 => 'application/videolan'], 'wmv' => [0 => 'video/x-ms-wmv', 1 => 'video/x-ms-asf'], 'au' => [0 => 'audio/x-au'], 'ac3' => [0 => 'audio/ac3'], 'flac' => [0 => 'audio/x-flac'], 'ogg' => [0 => 'audio/ogg', 1 => 'video/ogg', 2 => 'application/ogg'], 'kmz' => [0 => 'application/vnd.google-earth.kmz'], 'kml' => [0 => 'application/vnd.google-earth.kml+xml'], 'rtx' => [0 => 'text/richtext'], 'rtf' => [0 => 'text/rtf'], 'jar' => [0 => 'application/java-archive', 1 => 'application/x-java-application', 2 => 'application/x-jar'], 'zip' => [0 => 'application/x-zip', 1 => 'application/zip', 2 => 'application/x-zip-compressed', 3 => 'application/s-compressed', 4 => 'multipart/x-zip'], '7zip' => [0 => 'application/x-compressed'], 'xml' => [0 => 'application/xml', 1 => 'text/xml'], 'svg' => [0 => 'image/svg+xml'], '3g2' => [0 => 'video/3gpp2'], '3gp' => [0 => 'video/3gp', 1 => 'video/3gpp'], 'mp4' => [0 => 'video/mp4'], 'm4a' => [0 => 'audio/x-m4a'], 'f4v' => [0 => 'video/x-f4v'], 'flv' => [0 => 'video/x-flv'], 'webm' => [0 => 'video/webm'], 'aac' => [0 => 'audio/x-acc'], 'm4u' => [0 => 'application/vnd.mpegurl'], 'pdf' => [0 => 'application/pdf', 1 => 'application/octet-stream'], 'pptx' => [0 => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'], 'ppt' => [0 => 'application/powerpoint', 1 => 'application/vnd.ms-powerpoint', 2 => 'application/vnd.ms-office', 3 => 'application/msword'], 'docx' => [0 => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], 'xlsx' => [0 => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 1 => 'application/vnd.ms-excel'], 'xl' => [0 => 'application/excel'], 'xls' => [0 => 'application/msexcel', 1 => 'application/x-msexcel', 2 => 'application/x-ms-excel', 3 => 'application/x-excel', 4 => 'application/x-dos_ms_excel', 5 => 'application/xls', 6 => 'application/x-xls'], 'xsl' => [0 => 'text/xsl'], 'mpeg' => [0 => 'video/mpeg'], 'mov' => [0 => 'video/quicktime'], 'avi' => [0 => 'video/x-msvideo', 1 => 'video/msvideo', 2 => 'video/avi', 3 => 'application/x-troff-msvideo'], 'movie' => [0 => 'video/x-sgi-movie'], 'log' => [0 => 'text/x-log'], 'txt' => [0 => 'text/plain'], 'css' => [0 => 'text/css'], 'html' => [0 => 'text/html'], 'wav' => [0 => 'audio/x-wav', 1 => 'audio/wave', 2 => 'audio/wav'], 'xhtml' => [0 => 'application/xhtml+xml'], 'tar' => [0 => 'application/x-tar'], 'tgz' => [0 => 'application/x-gzip-compressed'], 'psd' => [0 => 'application/x-photoshop', 1 => 'image/vnd.adobe.photoshop'], 'exe' => [0 => 'application/x-msdownload'], 'js' => [0 => 'application/x-javascript'], 'mp3' => [0 => 'audio/mpeg', 1 => 'audio/mpg', 2 => 'audio/mpeg3', 3 => 'audio/mp3'], 'rar' => [0 => 'application/x-rar', 1 => 'application/rar', 2 => 'application/x-rar-compressed'], 'gzip' => [0 => 'application/x-gzip'], 'hqx' => [0 => 'application/mac-binhex40', 1 => 'application/mac-binhex', 2 => 'application/x-binhex40', 3 => 'application/x-mac-binhex40'], 'cpt' => [0 => 'application/mac-compactpro'], 'bin' => [0 => 'application/macbinary', 1 => 'application/mac-binary', 2 => 'application/x-binary', 3 => 'application/x-macbinary'], 'oda' => [0 => 'application/oda'], 'ai' => [0 => 'application/postscript'], 'smil' => [0 => 'application/smil'], 'mif' => [0 => 'application/vnd.mif'], 'wbxml' => [0 => 'application/wbxml'], 'wmlc' => [0 => 'application/wmlc'], 'dcr' => [0 => 'application/x-director'], 'dvi' => [0 => 'application/x-dvi'], 'gtar' => [0 => 'application/x-gtar'], 'php' => [0 => 'application/x-httpd-php', 1 => 'application/php', 2 => 'application/x-php', 3 => 'text/php', 4 => 'text/x-php', 5 => 'application/x-httpd-php-source'], 'swf' => [0 => 'application/x-shockwave-flash'], 'sit' => [0 => 'application/x-stuffit'], 'z' => [0 => 'application/x-compress'], 'mid' => [0 => 'audio/midi'], 'aif' => [0 => 'audio/x-aiff', 1 => 'audio/aiff'], 'ram' => [0 => 'audio/x-pn-realaudio'], 'rpm' => [0 => 'audio/x-pn-realaudio-plugin'], 'ra' => [0 => 'audio/x-realaudio'], 'rv' => [0 => 'video/vnd.rn-realvideo'], 'jp2' => [0 => 'image/jp2', 1 => 'video/mj2', 2 => 'image/jpx', 3 => 'image/jpm'], 'tiff' => [0 => 'image/tiff'], 'eml' => [0 => 'message/rfc822'], 'pem' => [0 => 'application/x-x509-user-cert', 1 => 'application/x-pem-file'], 'p10' => [0 => 'application/x-pkcs10', 1 => 'application/pkcs10'], 'p12' => [0 => 'application/x-pkcs12'], 'p7a' => [0 => 'application/x-pkcs7-signature'], 'p7c' => [0 => 'application/pkcs7-mime', 1 => 'application/x-pkcs7-mime'], 'p7r' => [0 => 'application/x-pkcs7-certreqresp'], 'p7s' => [0 => 'application/pkcs7-signature'], 'crt' => [0 => 'application/x-x509-ca-cert', 1 => 'application/pkix-cert'], 'crl' => [0 => 'application/pkix-crl', 1 => 'application/pkcs-crl'], 'pgp' => [0 => 'application/pgp'], 'gpg' => [0 => 'application/gpg-keys'], 'rsa' => [0 => 'application/x-pkcs7'], 'ics' => [0 => 'text/calendar'], 'zsh' => [0 => 'text/x-scriptzsh'], 'cdr' => [0 => 'application/cdr', 1 => 'application/coreldraw', 2 => 'application/x-cdr', 3 => 'application/x-coreldraw', 4 => 'image/cdr', 5 => 'image/x-cdr', 6 => 'zz-application/zz-winassoc-cdr'], 'wma' => [0 => 'audio/x-ms-wma'], 'vcf' => [0 => 'text/x-vcard'], 'srt' => [0 => 'text/srt'], 'vtt' => [0 => 'text/vtt'], 'ico' => [0 => 'image/x-icon', 1 => 'image/x-ico', 2 => 'image/vnd.microsoft.icon'], 'csv' => [0 => 'text/x-comma-separated-values', 1 => 'text/comma-separated-values', 2 => 'application/vnd.msexcel'], 'json' => [0 => 'application/json', 1 => 'text/json']];
}
