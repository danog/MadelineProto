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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Dns\Resolver;
use Amp\File\StatCache;
use Amp\Http\Client\HttpClient;
use Amp\Loop;
use Amp\Promise;
use Amp\Success;
use Closure;
use danog\MadelineProto\Async\AsyncConstruct;
use danog\MadelineProto\Db\DbArray;
use danog\MadelineProto\Db\DbPropertiesFactory;
use danog\MadelineProto\Db\DbPropertiesTrait;
use danog\MadelineProto\Db\MemoryArray;
use danog\MadelineProto\Ipc\Server;
use danog\MadelineProto\Loop\Generic\PeriodicLoopInternal;
use danog\MadelineProto\Loop\Update\FeedLoop;
use danog\MadelineProto\Loop\Update\SecretFeedLoop;
use danog\MadelineProto\Loop\Update\SeqLoop;
use danog\MadelineProto\Loop\Update\UpdateLoop;
use danog\MadelineProto\MTProtoTools\CombinedUpdatesState;
use danog\MadelineProto\MTProtoTools\MinDatabase;
use danog\MadelineProto\MTProtoTools\ReferenceDatabase;
use danog\MadelineProto\MTProtoTools\UpdatesState;
use danog\MadelineProto\Settings\Database\Memory;
use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\TL\TL;
use danog\MadelineProto\TL\TLCallback;
use Psr\Log\LoggerInterface;

use function Amp\File\exists;
use function Amp\File\size;

/**
 * Manages all of the mtproto stuff.
 *
 * @internal
 */
class MTProto extends AsyncConstruct implements TLCallback
{
    use \danog\Serializable;
    use \danog\MadelineProto\MTProtoTools\AuthKeyHandler;
    use \danog\MadelineProto\MTProtoTools\CallHandler;
    use \danog\MadelineProto\MTProtoTools\PeerHandler;
    use \danog\MadelineProto\MTProtoTools\UpdateHandler;
    use \danog\MadelineProto\MTProtoTools\Files;
    use \danog\MadelineProto\SecretChats\AuthKeyHandler;
    use \danog\MadelineProto\SecretChats\MessageHandler;
    use \danog\MadelineProto\SecretChats\ResponseHandler;
    use \danog\MadelineProto\SecretChats\SeqNoHandler;
    use \danog\MadelineProto\TL\Conversion\BotAPI;
    use \danog\MadelineProto\TL\Conversion\BotAPIFiles;
    use \danog\MadelineProto\TL\Conversion\TD;
    use \danog\MadelineProto\VoIP\AuthKeyHandler;
    use \danog\MadelineProto\Wrappers\Button;
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
    use DbPropertiesTrait;
    private const RSA_KEYS = [
        "-----BEGIN RSA PUBLIC KEY-----\n".
        "MIIBCgKCAQEA6LszBcC1LGzyr992NzE0ieY+BSaOW622Aa9Bd4ZHLl+TuFQ4lo4g\n".
        "5nKaMBwK/BIb9xUfg0Q29/2mgIR6Zr9krM7HjuIcCzFvDtr+L0GQjae9H0pRB2OO\n".
        "62cECs5HKhT5DZ98K33vmWiLowc621dQuwKWSQKjWf50XYFw42h21P2KXUGyp2y/\n".
        "+aEyZ+uVgLLQbRA1dEjSDZ2iGRy12Mk5gpYc397aYp438fsJoHIgJ2lgMv5h7WY9\n".
        "t6N/byY9Nw9p21Og3AoXSL2q/2IJ1WRUhebgAdGVMlV1fkuOQoEzR7EdpqtQD9Cs\n".
        "5+bfo3Nhmcyvk5ftB0WkJ9z6bNZ7yxrP8wIDAQAB\n".
        "-----END RSA PUBLIC KEY-----"
    ];
    private const TEST_RSA_KEYS = [
        "-----BEGIN RSA PUBLIC KEY-----\n".
        "MIIBCgKCAQEAyMEdY1aR+sCR3ZSJrtztKTKqigvO/vBfqACJLZtS7QMgCGXJ6XIR\n".
        "yy7mx66W0/sOFa7/1mAZtEoIokDP3ShoqF4fVNb6XeqgQfaUHd8wJpDWHcR2OFwv\n".
        "plUUI1PLTktZ9uW2WE23b+ixNwJjJGwBDJPQEQFBE+vfmH0JP503wr5INS1poWg/\n".
        "j25sIWeYPHYeOrFp/eXaqhISP6G+q2IeTaWTXpwZj4LzXq5YOpk4bYEQ6mvRq7D1\n".
        "aHWfYmlEGepfaYR8Q0YqvvhYtMte3ITnuSJs171+GDqpdKcSwHnd6FudwGO4pcCO\n".
        "j4WcDuXc2CTHgH8gFTNhp/Y8/SpDOhvn9QIDAQAB\n".
        "-----END RSA PUBLIC KEY-----"
    ];
    /**
     * Internal version of MadelineProto.
     *
     * Increased every time the default settings array or something big changes
     *
     * @internal
     *
     * @var int
     */
    const V = 154;
    /**
     * Release version.
     *
     * @var string
     */
    const RELEASE = '6.0';
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
     * Bad message error codes.
     *
     * @internal
     *
     * @var array
     */
    const BAD_MSG_ERROR_CODES = [16 => 'msg_id too low (most likely, client time is wrong; it would be worthwhile to synchronize it using msg_id notifications and re-send the original message with the â€œcorrectâ€ msg_id or wrap it in a container with a new msg_id if the original message had waited too long on the client to be transmitted)', 17 => 'msg_id too high (similar to the previous case, the client time has to be synchronized, and the message re-sent with the correct msg_id)', 18 => 'incorrect two lower order msg_id bits (the server expects client message msg_id to be divisible by 4)', 19 => 'container msg_id is the same as msg_id of a previously received message (this must never happen)', 20 => 'message too old, and it cannot be verified whether the server has received a message with this msg_id or not', 32 => 'msg_seqno too low (the server has already received a message with a lower msg_id but with either a higher or an equal and odd seqno)', 33 => 'msg_seqno too high (similarly, there is a message with a higher msg_id but with either a lower or an equal and odd seqno)', 34 => 'an even msg_seqno expected (irrelevant message), but odd received', 35 => 'odd msg_seqno expected (relevant message), but even received', 48 => 'incorrect server salt (in this case, the bad_server_salt response is received with the correct salt, and the message is to be re-sent with it)', 64 => 'invalid container'];
    /**
     * Localized message info flags.
     *
     * @internal
     *
     * @var array
     */
    const MSGS_INFO_FLAGS = [1 => 'nothing is known about the message (msg_id too low, the other party may have forgotten it)', 2 => 'message not received (msg_id falls within the range of stored identifiers; however, the other party has certainly not received a message like that)', 3 => 'message not received (msg_id too high; however, the other party has certainly not received it yet)', 4 => 'message received (note that this response is also at the same time a receipt acknowledgment)', 8 => ' and message already acknowledged', 16 => ' and message not requiring acknowledgment', 32 => ' and RPC query contained in message being processed or processing already complete', 64 => ' and content-related response to message already generated', 128 => ' and other party knows for a fact that message is already received'];
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
    /**
     * @internal
     */
    const GETUPDATES_HANDLER = 'getUpdates';
    /**
     * @internal
     */
    const TD_PARAMS_CONVERSION = ['updateNewMessage' => ['_' => 'updateNewMessage', 'disable_notification' => ['message', 'silent'], 'message' => ['message']], 'message' => ['_' => 'message', 'id' => ['id'], 'sender_user_id' => ['from_id'], 'chat_id' => ['peer_id', 'choose_chat_id_from_botapi'], 'send_state' => ['choose_incoming_or_sent'], 'can_be_edited' => ['choose_can_edit'], 'can_be_deleted' => ['choose_can_delete'], 'is_post' => ['post'], 'date' => ['date'], 'edit_date' => ['edit_date'], 'forward_info' => ['fwd_info', 'choose_forward_info'], 'reply_to_message_id' => ['reply_to_msg_id'], 'ttl' => ['choose_ttl'], 'ttl_expires_in' => ['choose_ttl_expires_in'], 'via_bot_user_id' => ['via_bot_id'], 'views' => ['views'], 'content' => ['choose_message_content'], 'reply_markup' => ['reply_markup']], 'messages.sendMessage' => ['chat_id' => ['peer'], 'reply_to_message_id' => ['reply_to_msg_id'], 'disable_notification' => ['silent'], 'from_background' => ['background'], 'input_message_content' => ['choose_message_content'], 'reply_markup' => ['reply_markup']]];
    /**
     * @internal
     */
    const TD_REVERSE = ['sendMessage' => 'messages.sendMessage'];
    /**
     * @internal
     */
    const TD_IGNORE = ['updateMessageID'];
    /**
     * Whether to generate only peer information.
     */
    const INFO_TYPE_PEER = 0;
    /**
     * Whether to generate only constructor information.
     */
    const INFO_TYPE_CONSTRUCTOR = 1;
    /**
     * Whether to generate only ID information.
     */
    const INFO_TYPE_ID = 2;
    /**
     * Whether to generate all information.
     */
    const INFO_TYPE_ALL = 3;
    /**
     * @internal
     */
    const BOTAPI_PARAMS_CONVERSION = ['disable_web_page_preview' => 'no_webpage', 'disable_notification' => 'silent', 'reply_to_message_id' => 'reply_to_msg_id', 'chat_id' => 'peer', 'text' => 'message'];
    /**
     * @internal
     */
    const DEFAULT_GETUPDATES_PARAMS = ['offset' => 0, 'limit' => null, 'timeout' => 100];
    /**
     * Array of references to all instances of MTProto.
     *
     * This seems like a recipe for memory leaks, but this is actually required to allow saving the session on shutdown.
     * When using a network I/O-based database+the EvDriver of AMPHP, calling die(); causes premature garbage collection of the event loop.
     * This garbage collection happens always, even if a reference to the event handler is already present elsewhere (probably ev dark magic).
     *
     * Finally, this causes the process to hang on shutdown, since the database driver cannot receive a reply from the server, because the event loop is down.
     *
     * To avoid this, we store each MTProto instance in here (unreferencing on shutdown in unreference()), and call serialize() on all instances before calling die; in Magic.
     *
     * @var self[]
     */
    public static array $references = [];
    /**
     * Instance of wrapper API.
     *
     * @var APIWrapper
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
     * @var Settings
     */
    public $settings;
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
     * @var int
     * @psalm-var self::NOT_LOGGED_IN|self::WAITING_*|self::LOGGED_IN
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
     * RSA keys.
     *
     * @var array<RSA>
     */
    private $test_rsa_keys = [];
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
     * @var DbArray|Promise[]
     */
    public $chats;

    /**
     * Cache of usernames for chats.
     *
     * @var DbArray|Promise[]
     */
    public $usernames;
    /**
     * Cached parameters for fetching channel participants.
     *
     * @var DbArray|Promise[]
     */
    public $channelParticipants;
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
     * @var DbArray|Promise[]
     */
    public $full_chats;
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
     */
    public ?PeriodicLoopInternal $checkTosLoop = null;
    /**
     * Phone config loop.
     */
    public ?PeriodicLoopInternal $phoneConfigLoop = null;
    /**
     * Config loop.
     */
    public ?PeriodicLoopInternal  $configLoop = null;
    /**
     * Call checker loop.
     */
    private ?PeriodicLoopInternal $callCheckerLoop = null;
    /**
     * Autoserialization loop.
     */
    private ?PeriodicLoopInternal $serializeLoop = null;
    /**
     * SEQ update loop.
     */
    private ?SeqLoop $seqUpdater = null;
    /**
     * IPC server.
     */
    private ?Server $ipcServer = null;
    /**
     * Feeder loops.
     *
     * @var array<\danog\MadelineProto\Loop\Update\FeedLoop>
     */
    public $feeders = [];
    /**
     * Secret chat feeder loops.
     *
     * @var array<\danog\MadelineProto\Loop\Update\SecretFeedLoop>
     */
    public $secretFeeders = [];
    /**
     * Updater loops.
     *
     * @var array<\danog\MadelineProto\Loop\Update\UpdateLoop>
     */
    public $updaters = [];
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
     * Snitch.
     */
    private Snitch $snitch;

    /**
     * DC list.
     */
    protected array $dcList = [
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
        ]
    ];

    /**
     * Nullcache array for storing main session file to DB.
     *
     * @var DbArray|Promise[]
     */
    public $session;

    /**
     * List of properties stored in database (memory or external).
     * @see DbPropertiesFactory
     * @var array
     */
    protected static array $dbProperties = [
        'chats' => 'array',
        'full_chats' => 'array',
        'channelParticipants' => 'array',
        'usernames' => 'array',
        'session' => [
            'type' => 'array',
            'config' => ['enableCache' => false]
        ]
    ];

    /**
     * Serialize session, returning object to serialize to db.
     *
     * @return \Generator
     */
    public function serializeSession(object $data): \Generator
    {
        if (!$this->session || $this->session instanceof MemoryArray) {
            return $data;
        }
        yield $this->session->set('data', $data);
        return $this->session;
    }
    /**
     * Serialize all instances.
     *
     * CALLED ONLY ON SHUTDOWN.
     *
     * @return void
     */
    public static function serializeAll(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        Logger::log('Prompting final serialization (SHUTDOWN)...');
        foreach (self::$references as $instance) {
            Tools::wait($instance->wrapper->serialize());
        }
        Logger::log('Done final serialization (SHUTDOWN)!');
    }

    /**
     * Constructor function.
     *
     * @param Settings|SettingsEmpty $settings Settings
     * @param ?APIWrapper            $wrapper  API wrapper
     *
     * @return void
     */
    public function __magic_construct(SettingsAbstract $settings, ?APIWrapper $wrapper = null)
    {
        if (static::class !== self::class || !$wrapper) {
            return;
        }
        self::$references[\spl_object_hash($this)] = $this;
        $this->wrapper = $wrapper;
        $this->setInitPromise($this->__construct_async($settings));
    }
    /**
     * Async constructor function.
     *
     * @param Settings|SettingsEmpty $settings Settings
     *
     * @return \Generator
     */
    public function __construct_async(SettingsAbstract $settings): \Generator
    {
        // Initialize needed stuffs
        Magic::start();
        // Parse and store settings
        $this->updateSettingsInternal($settings);
        // Actually instantiate needed classes like a boss
        yield from $this->cleanupProperties();
        // Start IPC server
        if (!$this->ipcServer) {
            try {
                $this->ipcServer = new Server($this);
                $this->ipcServer->setSettings($this->settings->getIpc());
                $this->ipcServer->setIpcPath($this->wrapper->session);
            } catch (\Throwable $e) {
                $this->logger->logger("Error while starting IPC server: $e", Logger::FATAL_ERROR);
            }
        }
        try {
            $this->ipcServer->start();
        } catch (\Throwable $e) {
            if (Magic::$isIpcWorker) {
                throw $e;
            }
            $this->logger->logger("Error while starting IPC server: $e", Logger::FATAL_ERROR);
        }
        // Load rsa keys
        $this->rsa_keys = [];
        foreach (self::RSA_KEYS as $key) {
            $key = yield from RSA::load($this->TL, $key);
            $this->rsa_keys[$key->fp] = $key;
        }
        $this->test_rsa_keys = [];
        foreach (self::TEST_RSA_KEYS as $key) {
            $key = yield from RSA::load($this->TL, $key);
            $this->test_rsa_keys[$key->fp] = $key;
        }
        // (re)-initialize TL
        $callbacks = [$this];
        if ($this->settings->getDb()->getEnableFileReferenceDb()) {
            $callbacks []= $this->referenceDatabase;
        }
        if (!($this->authorization['user']['bot'] ?? false) && $this->settings->getDb()->getEnableMinDb()) {
            $callbacks[] = $this->minDatabase;
        }
        $this->TL->init($this->settings->getSchema(), $callbacks);
        yield from $this->connectToAllDcs();
        $this->startLoops();
        $this->datacenter->curdc = 2;
        if ((!isset($this->authorization['user']['bot']) || !$this->authorization['user']['bot']) && $this->datacenter->getDataCenterConnection($this->datacenter->curdc)->hasTempAuthKey()) {
            try {
                $nearest_dc = yield from $this->methodCallAsyncRead('help.getNearestDc', []);
                $this->logger->logger(\sprintf(Lang::$current_lang['nearest_dc'], $nearest_dc['country'], $nearest_dc['nearest_dc']), Logger::NOTICE);
                if ($nearest_dc['nearest_dc'] != $nearest_dc['this_dc']) {
                    $this->settings->setDefaultDc($this->datacenter->curdc = (int) $nearest_dc['nearest_dc']);
                }
            } catch (RPCErrorException $e) {
                if ($e->rpc !== 'BOT_METHOD_INVALID') {
                    throw $e;
                }
            }
        }
        yield from $this->getConfig([]);
        $this->startUpdateSystem(true);
        $this->v = self::V;

        $this->settings->applyChanges();
        GarbageCollector::start();
    }
    /**
     * Set API wrapper needed for triggering serialization functions.
     *
     * @internal
     */
    public function setWrapper(APIWrapper $wrapper): void
    {
        $this->wrapper = $wrapper;
    }
    /**
     * Get API wrapper.
     *
     * @internal
     */
    public function getWrapper(): APIWrapper
    {
        return $this->wrapper;
    }
    /**
     * Sleep function.
     *
     * @return array
     */
    public function __sleep(): array
    {
        $db = $this->settings->getDb();
        if ($db instanceof Memory && $db->getCleanup()) {
            Tools::wait($this->cleanup());
        }
        $res = [
            // Databases
            'chats',
            'full_chats',
            'referenceDatabase',
            'minDatabase',
            'channelParticipants',
            'usernames',

            'tmpDbPrefix',

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
            'webTemplate',

            // Settings
            'settings',
            'config',
            'dcList',

            // Authorization keys
            'datacenter',

            // Authorization state
            'authorization',
            'authorized',
            'authorized_dc',

            // Authorization cache
            'rsa_keys',
            'test_rsa_keys',
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

            // Report URI
            'reportDest',

            'calls',
            'snitch',
        ];
        if (!$this->updateHandler instanceof Closure) {
            $res[] = 'updateHandler';
        }
        return $res;
    }

    /**
     * Cleanup memory and session file.
     *
     * @return \Generator
     */
    public function cleanup(): \Generator
    {
        $this->referenceDatabase = new ReferenceDatabase($this);
        yield from $this->referenceDatabase->init();
        $callbacks = [$this];
        if ($this->settings->getDb()->getEnableFileReferenceDb()) {
            $callbacks[] = $this->referenceDatabase;
        }
        if ($this->settings->getDb()->getEnableMinDb() && !($this->authorization['user']['bot'] ?? false)) {
            $callbacks[] = $this->minDatabase;
        }
        $this->TL->updateCallbacks($callbacks);
    }

    private function fillUsernamesCache(): \Generator
    {
        if (!$this->settings->getDb()->getEnableUsernameDb()) {
            yield $this->usernames->clear();
            return;
        }
        if (!yield $this->usernames->count()) {
            $this->logger('Filling database cache. This can take few minutes.', Logger::WARNING);
            $iterator = $this->chats->getIterator();
            while (yield $iterator->advance()) {
                [$id, $chat] = $iterator->getCurrent();
                if (isset($chat['username'])) {
                    $this->usernames[\strtolower($chat['username'])] = $this->getId($chat);
                }
            }
            $this->logger('Cache filled.', Logger::WARNING);
        }
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
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }
    /**
     * Get PSR logger.
     */
    public function getPsrLogger(): LoggerInterface
    {
        return $this->logger->getPsrLogger();
    }
    /**
     * Get async HTTP client.
     *
     * @return \Amp\Http\Client\HttpClient
     */
    public function getHTTPClient(): HttpClient
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
     * @return \Generator
     *
     * @psalm-return \Generator<int, Promise<string>, mixed, string>
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
     * Get main DC ID.
     *
     * @return int|string
     */
    public function getDataCenterId()
    {
        return $this->datacenter->curdc;
    }
    /**
     * Prompt serialization of instance.
     *
     * @internal
     *
     * @return void
     */
    public function serialize()
    {
        if ($this->wrapper && $this->inited()) {
            $this->wrapper->serialize();
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
            $this->callCheckerLoop = new PeriodicLoopInternal($this, [$this, 'checkCalls'], 'call check', 10 * 1000);
        }
        if (!$this->serializeLoop) {
            $this->serializeLoop = new PeriodicLoopInternal($this, [$this, 'serialize'], 'serialize', $this->settings->getSerialization()->getInterval() * 1000);
        }
        if (!$this->phoneConfigLoop) {
            $this->phoneConfigLoop = new PeriodicLoopInternal($this, [$this, 'getPhoneConfig'], 'phone config', 24 * 3600 * 1000);
        }
        if (!$this->checkTosLoop) {
            $this->checkTosLoop = new PeriodicLoopInternal($this, [$this, 'checkTos'], 'TOS', 24 * 3600 * 1000);
        }
        if (!$this->configLoop) {
            $this->configLoop = new PeriodicLoopInternal($this, [$this, 'getConfig'], 'config', 24 * 3600 * 1000);
        }
        if (!$this->ipcServer) {
            try {
                $this->ipcServer = new Server($this);
                $this->ipcServer->setSettings($this->settings->getIpc());
                $this->ipcServer->setIpcPath($this->wrapper->session);
            } catch (\Throwable $e) {
                $this->logger->logger("Error while starting IPC server: $e", Logger::FATAL_ERROR);
            }
        }
        $this->callCheckerLoop->start();
        $this->serializeLoop->start();
        $this->phoneConfigLoop->start();
        $this->configLoop->start();
        $this->checkTosLoop->start();
        try {
            $this->ipcServer->start();
        } catch (\Throwable $e) {
            if (Magic::$isIpcWorker) {
                throw $e;
            }
            $this->logger->logger("Error while starting IPC server: $e", Logger::FATAL_ERROR);
        }
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
        if ($this->ipcServer) {
            $this->ipcServer->signal(null);
            $this->ipcServer = null;
        }
    }
    /**
     * Clean up properties from previous versions of MadelineProto.
     *
     * @internal
     *
     * @return \Generator
     *
     * @psalm-return \Generator<mixed, mixed, mixed, void>
     */
    private function cleanupProperties(): \Generator
    {
        if (!$this->channels_state instanceof CombinedUpdatesState) {
            $this->channels_state = new CombinedUpdatesState($this->channels_state);
        }
        if (isset($this->updates_state)) {
            if (!$this->updates_state instanceof UpdatesState) {
                $this->updates_state = new UpdatesState($this->updates_state);
            }
            $this->channels_state->__construct([UpdateLoop::GENERIC => $this->updates_state]);
            unset($this->updates_state);
        }
        if (!isset($this->datacenter)) {
            $this->datacenter ??= new DataCenter($this, $this->dcList, $this->settings->getConnection());
        }
        if (!isset($this->snitch)) {
            $this->snitch = new Snitch;
        }
        $db = [];
        if (!isset($this->referenceDatabase)) {
            $this->referenceDatabase = new ReferenceDatabase($this);
            $db []= $this->referenceDatabase->init();
        } else {
            $db []= $this->referenceDatabase->init();
        }
        if (!isset($this->minDatabase)) {
            $this->minDatabase = new MinDatabase($this);
            $db []= $this->minDatabase->init();
        } else {
            $db []= $this->minDatabase->init();
        }
        if (!isset($this->TL)) {
            $this->TL = new TL($this);
            $callbacks = [$this, $this->referenceDatabase];
            if (!($this->authorization['user']['bot'] ?? false)) {
                $callbacks[] = $this->minDatabase;
            }
            $this->TL->init($this->settings->getSchema(), $callbacks);
        }

        $db []= $this->initDb($this);
        yield Tools::all($db);
        yield from $this->fillUsernamesCache();

        if (!$this->settings->getDb()->getEnableFullPeerDb()) {
            yield $this->full_chats->clear();
        }
        if (!$this->settings->getDb()->getEnablePeerInfoDb()) {
            if (yield $this->chats->isset(0)) {
                return;
            }
            $this->logger("Cleaning up peer database...");
            $k = 0;
            $total = yield $this->chats->count();
            $iterator = $this->chats->getIterator();
            while (yield $iterator->advance()) {
                [$key, $value] = $iterator->getCurrent();
                $value = [
                    '_' => $value['_'],
                    'id' => $value['id'],
                    'access_hash' => $value['access_hash'] ?? null,
                    'min' => $value['min'] ?? false,
                ];
                $k++;
                if ($k % 500 === 0 || $k === $total) {
                    $this->logger("Cleaning up peer database ($k/$total)...");
                    yield $this->chats->set($key, $value);
                } else {
                    $this->chats->set($key, $value);
                }
            }
            yield $this->chats->set(0, []);
            $this->logger("Cleaned up peer database!");
        } elseif (yield $this->chats->isset(0)) {
            $this->chats->unset(0);
        }
    }

    /**
     * Upgrade MadelineProto instance.
     *
     * @return \Generator
     * @throws Exception
     * @throws RPCErrorException
     * @throws \Throwable
     */
    private function upgradeMadelineProto(): \Generator
    {
        if (!isset($this->snitch)) {
            $this->snitch = new Snitch;
        }
        $this->logger->logger(Lang::$current_lang['serialization_ofd'], Logger::WARNING);
        foreach ($this->datacenter->getDataCenterConnections() as $dc_id => $socket) {
            if ($this->authorized === self::LOGGED_IN && \strpos($dc_id, '_') === false && $socket->hasPermAuthKey() && $socket->hasTempAuthKey()) {
                $socket->bind();
                $socket->authorized(true);
            }
        }
        $this->settings->setSchema(new TLSchema);

        yield from $this->initDb($this);

        if (!isset($this->secret_chats)) {
            $this->secret_chats = [];
        }
        $iterator = $this->full_chats->getIterator();
        while (yield $iterator->advance()) {
            [$id, $full] = $iterator->getCurrent();
            if (isset($full['full'], $full['last_update'])) {
                yield $this->full_chats->set($id, ['full' => $full['full'], 'last_update' => $full['last_update']]);
            }
        }

        if (isset($this->channel_participants)) {
            if (\is_array($this->channel_participants)) {
                foreach ($this->channel_participants as $channelId => $filters) {
                    foreach ($filters as $filter => $qs) {
                        foreach ($qs as $q => $offsets) {
                            foreach ($offsets as $offset => $limits) {
                                foreach ($limits as $limit => $data) {
                                    $this->channelParticipants[$this->participantsKey($channelId, $filter, $q, $offset, $limit)] = $data;
                                }
                            }
                        }
                    }
                    unset($this->channel_participants[$channelId]);
                }
            } else {
                self::$dbProperties['channel_participants'] = 'array';
                yield from $this->initDb($this);
                $iterator = $this->channel_participants->getIterator();
                while (yield $iterator->advance()) {
                    [$channelId, $filters] = $iterator->getCurrent();
                    foreach ($filters as $filter => $qs) {
                        foreach ($qs as $q => $offsets) {
                            foreach ($offsets as $offset => $limits) {
                                foreach ($limits as $limit => $data) {
                                    $this->channelParticipants[$this->participantsKey($channelId, $filter, $q, $offset, $limit)] = $data;
                                }
                            }
                        }
                    }
                    unset($this->channel_participants[$channelId]);
                }
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
        unset($chat);

        $this->resetMTProtoSession(true, true);
        $this->config = ['expires' => -1];
        $this->dh_config = ['version' => 0];
        yield from $this->__construct_async($this->settings);
        foreach ($this->secret_chats as $chat => $data) {
            try {
                if (isset($this->secret_chats[$chat]) && $this->secret_chats[$chat]['InputEncryptedChat'] !== null) {
                    yield from $this->notifyLayer($chat);
                }
            } catch (\danog\MadelineProto\RPCErrorException $e) {
            }
        }
    }
    /**
     * Post-deserialization initialization function.
     *
     * @param Settings|SettingsEmpty $settings New settings
     * @param APIWrapper             $wrapper  API wrapper
     *
     * @internal
     *
     * @return \Generator
     */
    public function wakeup(SettingsAbstract $settings, APIWrapper $wrapper): \Generator
    {
        // Set reference to itself
        self::$references[\spl_object_hash($this)] = $this;
        // Set API wrapper
        $this->wrapper = $wrapper;
        // BC stuff
        if ($this->authorized === true) {
            $this->authorized = self::LOGGED_IN;
        }
        if (!isset($this->snitch)) {
            $this->snitch = new Snitch;
        }
        // Convert old array settings to new settings object
        if (\is_array($this->settings)) {
            if (($this->settings['updates']['callback'] ?? '') === 'getUpdatesUpdateHandler') {
                /** @psalm-suppress InvalidPropertyAssignmentValue */
                $this->settings['updates']['callback'] = [$this, 'getUpdatesUpdateHandler'];
            }
            if (\is_callable($this->settings['updates']['callback'] ?? null)) {
                $this->updateHandler = $this->settings['updates']['callback'];
            }

            /** @psalm-suppress InvalidArrayOffset */
            $this->dcList = $this->settings['connection'] ?? $this->dcList;
        }
        $this->settings = Settings::parseFromLegacy($this->settings);
        // Clean up phone call array
        foreach ($this->calls as $id => $controller) {
            if (!\is_object($controller)) {
                unset($this->calls[$id]);
            } elseif ($controller->getCallState() === VoIP::CALL_STATE_ENDED) {
                $controller->setMadeline($this);
                $controller->discard();
            } else {
                $controller->setMadeline($this);
            }
        }

        $this->forceInit(false);
        $this->setInitPromise($this->wakeupAsync($settings));

        return $this->initAsynchronously();
    }
    /**
     * Async wakeup function.
     *
     * @param Settings|SettingsEmpty $settings New settings
     *
     * @return \Generator
     */
    private function wakeupAsync(SettingsAbstract $settings): \Generator
    {
        // Setup one-time stuffs
        Magic::start();
        $this->settings->getConnection()->init();
        // Setup logger
        $this->setupLogger();
        // Setup language
        Lang::$current_lang =& Lang::$lang['en'];
        if (Lang::$lang[$this->settings->getAppInfo()->getLangCode()] ?? false) {
            Lang::$current_lang =& Lang::$lang[$this->settings->getAppInfo()->getLangCode()];
        }
        // Reset MTProto session (not related to user session)
        $this->resetMTProtoSession();
        // Update settings from constructor
        $this->updateSettingsInternal($settings);
        // Session update process for BC
        $forceDialogs = false;
        if (!isset($this->v)
            || $this->v !== self::V
            || $this->settings->getSchema()->needsUpgrade()) {
            yield from $this->upgradeMadelineProto();
            $forceDialogs = true;
        }
        // Cleanup old properties, init new stuffs
        yield from $this->cleanupProperties();
        // Update TL callbacks
        $callbacks = [$this];
        if ($this->settings->getDb()->getEnableFileReferenceDb()) {
            $callbacks[] = $this->referenceDatabase;
        }
        if ($this->settings->getDb()->getEnableMinDb() && !($this->authorization['user']['bot'] ?? false)) {
            $callbacks[] = $this->minDatabase;
        }
        // Connect to all DCs, start internal loops
        yield from $this->connectToAllDcs();
        if (yield from $this->fullGetSelf()) {
            $this->authorized = self::LOGGED_IN;
            $this->setupLogger();
            $this->startLoops();
            yield from $this->getCdnConfig($this->datacenter->curdc);
            yield from $this->initAuthorization();
        } else {
            $this->startLoops();
        }
        // onStart event handler
        if ($this->event_handler && \class_exists($this->event_handler) && \is_subclass_of($this->event_handler, EventHandler::class)) {
            yield from $this->setEventHandler($this->event_handler);
        } else {
            if ($this->updateHandler === [$this, 'eventUpdateHandler']) {
                $this->setNoop();
            }
            $this->event_handler = null;
            $this->event_handler_instance = null;
        }
        $this->startUpdateSystem(true);
        if ($this->authorized === self::LOGGED_IN && !$this->authorization['user']['bot'] && $this->settings->getPeer()->getCacheAllPeersOnStartup()) {
            yield from $this->getDialogs($forceDialogs);
        }
        if ($this->authorized === self::LOGGED_IN) {
            $this->logger->logger(Lang::$current_lang['getupdates_deserialization'], Logger::NOTICE);
            yield $this->updaters[UpdateLoop::GENERIC]->resume();
        }
        $this->updaters[UpdateLoop::GENERIC]->start();

        GarbageCollector::start();
    }
    /**
     * Unreference instance, allowing destruction.
     *
     * @internal
     *
     * @return void
     */
    public function unreference(): void
    {
        if (!isset($this->logger)) {
            $this->logger = new Logger(new \danog\MadelineProto\Settings\Logger);
        }
        $this->logger->logger("Will unreference instance");
        if (isset(self::$references[\spl_object_hash($this)])) {
            unset(self::$references[\spl_object_hash($this)]);
        }
        $this->stopLoops();
        if (isset($this->seqUpdater)) {
            $this->seqUpdater->signal(true);
        }
        if (isset($this->channels_state)) {
            $channelIds = [];
            foreach ($this->channels_state->get() as $state) {
                $channelIds[] = $state->getChannel();
            }
            \sort($channelIds);
            foreach ($channelIds as $channelId) {
                if (isset($this->feeders[$channelId])) {
                    $this->feeders[$channelId]->signal(true);
                }
                if (isset($this->updaters[$channelId])) {
                    $this->updaters[$channelId]->signal(true);
                }
            }
        }
        if (isset($this->datacenter)) {
            foreach ($this->datacenter->getDataCenterConnections() as $datacenter) {
                $datacenter->setExtra($this);
                $datacenter->disconnect();
            }
        }
        $this->logger->logger("Unreferenced instance");
    }
    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->logger('Shutting down MadelineProto (MTProto)');
        $this->unreference();
        $this->logger("Successfully destroyed MadelineProto");
    }
    /**
     * Restart IPC server instance.
     *
     * @internal
     */
    public function restartIpcServer(): Promise
    {
        return new Success(); // Can only be called from client
    }
    /**
     * Whether we're an IPC client instance.
     *
     * @return boolean
     */
    public function isIpc(): bool
    {
        return false;
    }
    /**
     * Whether we're an IPC server process (as opposed to an event handler).
     *
     * @return boolean
     */
    public function isIpcWorker(): bool
    {
        return Magic::$isIpcWorker;
    }
    /**
     * Parse, update and store settings.
     *
     * @param SettingsAbstract $settings Settings
     *
     * @return \Generator
     */
    public function updateSettings(SettingsAbstract $settings): \Generator
    {
        $this->updateSettingsInternal($settings);

        if ($this->settings->getDb()->hasChanged()) {
            yield from $this->initDb($this);
            $this->settings->getDb()->applyChanges();
        }
        if ($this->settings->getIpc()->hasChanged()) {
            $this->ipcServer->setSettings($this->settings->getIpc()->applyChanges());
        }
        if ($this->settings->getSerialization()->hasChanged()) {
            $this->serializeLoop->signal(true);
            $this->serializeLoop = new PeriodicLoopInternal($this, [$this, 'serialize'], 'serialize', $this->settings->getSerialization()->applyChanges()->getInterval() * 1000);
        }
        if ($this->settings->getAuth()->hasChanged()
            || $this->settings->getConnection()->hasChanged()
            || $this->settings->getSchema()->hasChanged()
            || $this->settings->getSchema()->needsUpgrade()) {
            yield from $this->__construct_async($this->settings);
        }
    }
    /**
     * Parse, update and store settings.
     *
     * @param SettingsAbstract $settings Settings
     *
     * @return void
     */
    private function updateSettingsInternal(SettingsAbstract $settings): void
    {
        if ($settings instanceof SettingsEmpty) {
            if (!isset($this->settings)) {
                $this->settings = new Settings;
            } else {
                return;
            }
        } else {
            if (!isset($this->settings)) {
                if ($settings instanceof Settings) {
                    $this->settings = $settings;
                } else {
                    $this->settings = new Settings;
                    $this->settings->merge($settings);
                }
            } else {
                $this->settings->merge($settings);
            }
        }
        if (!$this->settings->getAppInfo()->hasApiInfo()) {
            throw new \danog\MadelineProto\Exception(Lang::$current_lang['api_not_set'], 0, null, 'MadelineProto', 1);
        }

        // Setup logger
        if ($this->settings->getLogger()->hasChanged() || !$this->logger) {
            $this->setupLogger();
        }
    }
    /**
     * Return current settings.
     *
     * @return Settings
     */
    public function getSettings(): Settings
    {
        return $this->settings;
    }
    /**
     * Setup logger.
     *
     * @return void
     */
    public function setupLogger(): void
    {
        $this->logger = new Logger(
            $this->settings->getLogger(),
            $this->authorization['user']['username'] ?? $this->authorization['user']['id'] ?? ''
        );
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
            if ((!$dc->isAuthorized() || !$dc->hasTempAuthKey()) && !$dc->isCDN()) {
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
        $this->channels_state->get(FeedLoop::GENERIC);
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
        $this->datacenter->__construct($this, $this->dcList, $this->settings->getConnection(), $reconnectAll);
        $dcs = [];
        foreach ($this->datacenter->getDcs() as $new_dc) {
            $dcs[] = $this->datacenter->dcConnect($new_dc);
        }
        yield \danog\MadelineProto\Tools::all($dcs);
        yield from $this->initAuthorization();
        yield from $this->parseConfig();
        $dcs = [];
        foreach ($this->datacenter->getDcs(false) as $new_dc) {
            $dcs[] = $this->datacenter->dcConnect($new_dc);
        }
        yield \danog\MadelineProto\Tools::all($dcs);
        yield from $this->initAuthorization();
        yield from $this->parseConfig();
        yield from $this->getPhoneConfig();
    }
    /**
     * Clean up MadelineProto session after logout.
     *
     * @internal
     *
     * @return \Generator<void>
     */
    public function resetSession(): \Generator
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
            if (isset($this->updaters[$channelId])) {
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

        yield from $this->initDb($this, true);

        $this->tos = ['expires' => 0, 'accepted' => true];
        $this->dialog_params = ['_' => 'MadelineProto.dialogParams', 'limit' => 0, 'offset_date' => 0, 'offset_id' => 0, 'offset_peer' => ['_' => 'inputPeerEmpty'], 'count' => 0];

        $this->referenceDatabase = new ReferenceDatabase($this);
        yield from $this->referenceDatabase->init();

        $this->minDatabase = new MinDatabase($this);
        yield from $this->minDatabase->init();
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
            $pts = $channelId ? \max(1, $pts - 1000000) : ($pts > 4000000 ? $pts - 1000000 : \max(1, $pts - 1000000));
            $newStates[$channelId] = new UpdatesState(['pts' => $pts], $channelId);
        }
        \sort($channelIds);
        foreach ($channelIds as $channelId) {
            if (isset($this->feeders[$channelId])) {
                $this->feeders[$channelId]->signal(true);
            }
            if (isset($this->updaters[$channelId])) {
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
        if (!$this->inited() && !$anyway) {
            $this->logger("Not starting update system");
            return;
        }
        $this->logger("Starting update system");
        foreach ($this->secret_chats as $id => $chat) {
            if (!isset($this->secretFeeders[$id])) {
                $this->secretFeeders[$id] = new SecretFeedLoop($this, $id);
            }
            if ($this->secretFeeders[$id]->start() && isset($this->secretFeeders[$id])) {
                $this->secretFeeders[$id]->resume();
            }
        }
        if (!isset($this->seqUpdater)) {
            $this->seqUpdater = new SeqLoop($this);
        }
        $this->channels_state->get(FeedLoop::GENERIC);
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
        $this->flushAll();
        if ($this->seqUpdater->start()) {
            $this->seqUpdater->resume();
        }
    }
    /**
     * Flush all datacenter connections.
     *
     * @return void
     */
    private function flushAll(): void
    {
        foreach ($this->datacenter->getDataCenterConnections() as $datacenter) {
            $datacenter->flush();
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
        if ($this->authorized === self::LOGGED_IN
            && \class_exists(VoIPServerConfigInternal::class)
            && !$this->authorization['user']['bot']
            && $this->datacenter->getDataCenterConnection($this->settings->getDefaultDc())->hasTempAuthKey()) {
            $this->logger->logger('Fetching phone config...');
            VoIPServerConfig::updateDefault(yield from $this->methodCallAsyncRead('phone.getCallConfig', [], $this->settings->getDefaultDcParams()));
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
            foreach ((yield from $this->methodCallAsyncRead('help.getCdnConfig', [], ['datacenter' => $datacenter]))['public_keys'] as $curkey) {
                $curkey = yield from RSA::load($this->TL, $curkey['public_key']);
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
        $this->config = empty($config) ? yield from $this->methodCallAsyncRead('help.getConfig', $config, $options ?: $this->settings->getDefaultDcParams()) : $config;
        yield from $this->parseConfig();
        $this->logger->logger(Lang::$current_lang['config_updated'], Logger::NOTICE);
        $this->logger->logger($this->config, Logger::NOTICE);
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
            yield from $this->parseDcOptions($options);
        }
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
        $previous = $this->dcList;
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
            if (\is_numeric($id)) {
                $id = (int) $id;
            }
            unset($dc['cdn'], $dc['media_only'], $dc['id'], $dc['ipv6']);
            $this->dcList[$test][$ipv6][$id] = $dc;
        }
        $curdc = $this->datacenter->curdc;
        if ($previous !== $this->dcList && (!$this->datacenter->has($curdc) || $this->datacenter->getDataCenterConnection($curdc)->byIPAddress())) {
            $this->logger->logger('Got new DC options, reconnecting');
            yield from $this->connectToAllDcs(false);
        }
        $this->datacenter->curdc = $curdc;
    }
    /**
     * Get info about the logged-in user, cached.
     *
     * @return array|bool
     */
    public function getSelf()
    {
        return $this->authorization['user'] ?? false;
    }
    /**
     * Get info about the logged-in user, not cached.
     *
     * @return \Generator<array|bool>
     */
    public function fullGetSelf(): \Generator
    {
        try {
            $this->authorization = ['user' => (yield from $this->methodCallAsyncRead('users.getUsers', ['id' => [['_' => 'inputUserSelf']]]))[0]];
        } catch (RPCErrorException $e) {
            $this->logger->logger($e->getMessage());
            return false;
        }
        return $this->authorization['user'];
    }
    /**
     * Get authorization info.
     *
     * @return int
     */
    public function getAuthorization(): int
    {
        return $this->authorized;
    }
    /**
     * Get current password hint.
     *
     * @return string
     */
    public function getHint(): string
    {
        if ($this->authorized !== self::WAITING_PASSWORD) {
            throw new Exception("Not waiting for the password!");
        }
        return $this->authorization['hint'];
    }
    /**
     * IDs of peers where to report errors.
     *
     * @var int[]
     */
    private $reportDest = [];
    /**
     * Check if has report peers.
     *
     * @return boolean
     */
    public function hasReportPeers(): bool
    {
        return (bool) $this->reportDest;
    }
    /**
     * Get a message to show to the user when starting the bot.
     *
     * @param string $message
     */
    public function getWebMessage(string $message): string
    {
        Logger::log($message);

        $warning = '';
        if (!$this->hasReportPeers() && $this->hasEventHandler()) {
            Logger::log("!!! Warning: no report peers are set, please add the following method to your event handler !!!", Logger::FATAL_ERROR);
            Logger::log("!!! public function getReportPeers() { return '@yourtelegramusername'; } !!!", Logger::FATAL_ERROR);
            $warning .= "<h2 style='color:red;'>Warning: no report peers are set, please add the following method to your event handler:</h2>";
            $warning .= "<code>public function getReportPeers() { return '@yourtelegramusername'; }</code>";
        }
        if (!Magic::$hasOpenssl) {
            $warning .= "<h2 style='color:red;'>Warning: the openssl extension is not installed, please install it to speed up MadelineProto</h2>";
        }
        return "<html><body><h1>$message</h1>$warning</body></html>";
    }

    /**
     * Set peer(s) where to send errors occurred in the event loop.
     *
     * @param int|string $userOrId Username(s) or peer ID(s)
     *
     * @return \Generator
     */
    public function setReportPeers($userOrId): \Generator
    {
        if (!(\is_array($userOrId) && !isset($userOrId['_']) && !isset($userOrId['id']))) {
            $userOrId = [$userOrId];
        }
        foreach ($userOrId as $k => &$peer) {
            try {
                $peer = (yield from $this->getInfo($peer))['bot_api_id'];
                if ($peer === 101374607) {
                    unset($userOrId[$k]);
                }
            } catch (\Throwable $e) {
                unset($userOrId[$k]);
                $this->logger("Could not obtain info about report peer $peer: $e", Logger::FATAL_ERROR);
            }
        }
        /** @var int[] $userOrId */
        $this->reportDest = $userOrId;
    }
    /**
     * Report an error to the previously set peer.
     *
     * @param string $message   Error to report
     * @param string $parseMode Parse mode
     *
     * @return \Generator
     */
    public function report(string $message, string $parseMode = ''): \Generator
    {
        if (!$this->reportDest) {
            return;
        }
        $file = null;
        if ($this->settings->getLogger()->getType() === Logger::FILE_LOGGER
            && $path = $this->settings->getLogger()->getExtra()) {
            StatCache::clear($path);
            if (!yield exists($path)) {
                $message = "!!! WARNING !!!\nThe logfile does not exist, please DO NOT delete the logfile to avoid errors in MadelineProto!\n\n$message";
            } elseif (!yield size($path)) {
                $message = "!!! WARNING !!!\nThe logfile is empty, please DO NOT delete the logfile to avoid errors in MadelineProto!\n\n$message";
            } else {
                $file = yield from $this->methodCallAsyncRead(
                    'messages.uploadMedia',
                    [
                        'peer' => $this->reportDest[0],
                        'media' => [
                            '_' => 'inputMediaUploadedDocument',
                            'file' => $path,
                            'attributes' => [
                                ['_' => 'documentAttributeFilename', 'file_name' => 'MadelineProto.log']
                            ]
                        ]
                    ]
                );
            }
        }
        $sent = true;
        foreach ($this->reportDest as $id) {
            try {
                yield from $this->methodCallAsyncRead('messages.sendMessage', ['peer' => $id, 'message' => $message, 'parse_mode' => $parseMode]);
                if ($file) {
                    yield from $this->methodCallAsyncRead('messages.sendMedia', ['peer' => $id, 'media' => $file]);
                }
                $sent &= true;
            } catch (\Throwable $e) {
                $sent &= false;
                $this->logger("While reporting to $id: $e", Logger::FATAL_ERROR);
            }
        }
        if ($sent && $file) {
            \ftruncate($this->logger->stdout->getResource(), 0);
            $this->logger->logger("Reported!");
        }
    }
    /**
     * Get full list of MTProto and API methods.
     *
     * @return array
     */
    public function getAllMethods(): array
    {
        $methods = [];
        foreach ($this->getTL()->getMethods()->by_id as $method) {
            $methods[] = $method['method'];
        }
        return \array_merge($methods, \get_class_methods(InternalDoc::class));
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
            \array_fill_keys(
                [
                    'InputPeer',
                ],
                [$this, 'getInputPeer']
            ),
            \array_fill_keys(
                [
                    'InputUser',
                    'InputChannel',
                ],
                [$this, 'getInputConstructor']
            ),
            \array_fill_keys(
                [
                    'User',
                    'Chat',
                    'Peer',
                    'InputDialogPeer',
                    'InputNotifyPeer'
                ],
                [$this, 'getInfo']
            ),
            \array_fill_keys(
                [
                    'InputMedia',
                    'InputDocument',
                    'InputPhoto'
                ],
                [$this, 'getFileInfo']
            ),
            \array_fill_keys(
                ['InputFileLocation'],
                [$this, 'getDownloadInfo']
            )
        );
    }
    /**
     * Get debug information for var_dump.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        $vars = \get_object_vars($this);
        unset($vars['full_chats'], $vars['chats'], $vars['referenceDatabase'], $vars['minDatabase'], $vars['TL']);
        return $vars;
    }
}
