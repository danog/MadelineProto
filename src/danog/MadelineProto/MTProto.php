<?php

declare(strict_types=1);

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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\DeferredFuture;
use Amp\Dns\DnsResolver;
use Amp\Future;
use Amp\Http\Client\HttpClient;
use Amp\Sync\LocalMutex;
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
use danog\MadelineProto\MTProtoTools\AuthKeyHandler;
use danog\MadelineProto\MTProtoTools\CallHandler;
use danog\MadelineProto\MTProtoTools\CombinedUpdatesState;
use danog\MadelineProto\MTProtoTools\Files;
use danog\MadelineProto\MTProtoTools\MinDatabase;
use danog\MadelineProto\MTProtoTools\PeerHandler;
use danog\MadelineProto\MTProtoTools\ReferenceDatabase;
use danog\MadelineProto\MTProtoTools\UpdateHandler;
use danog\MadelineProto\MTProtoTools\UpdatesState;
use danog\MadelineProto\SecretChats\MessageHandler;
use danog\MadelineProto\SecretChats\ResponseHandler;
use danog\MadelineProto\SecretChats\SeqNoHandler;
use danog\MadelineProto\Settings\Database\Memory;
use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\TL\Conversion\BotAPI;
use danog\MadelineProto\TL\Conversion\BotAPIFiles;
use danog\MadelineProto\TL\Conversion\TD;
use danog\MadelineProto\TL\TL;
use danog\MadelineProto\TL\TLCallback;
use danog\MadelineProto\Wrappers\Ads;
use danog\MadelineProto\Wrappers\Button;
use danog\MadelineProto\Wrappers\DialogHandler;
use danog\MadelineProto\Wrappers\Events;
use danog\MadelineProto\Wrappers\Login;
use danog\MadelineProto\Wrappers\Loop;
use danog\MadelineProto\Wrappers\Start;
use danog\MadelineProto\Wrappers\Templates;
use danog\MadelineProto\Wrappers\TOS;
use Psr\Log\LoggerInterface;
use Throwable;
use Webmozart\Assert\Assert;

use const DEBUG_BACKTRACE_IGNORE_ARGS;
use function Amp\async;
use function Amp\File\getSize;
use function Amp\File\touch as touchAsync;
use function Amp\Future\await;

use function time;

/**
 * Manages all of the mtproto stuff.
 *
 * @internal
 */
final class MTProto implements TLCallback, LoggerGetter
{
    use AuthKeyHandler;
    use CallHandler;
    use PeerHandler;
    use UpdateHandler;
    use Files;
    use \danog\MadelineProto\SecretChats\AuthKeyHandler;
    use MessageHandler;
    use ResponseHandler;
    use SeqNoHandler;
    use BotAPI;
    use BotAPIFiles;
    use TD;
    use \danog\MadelineProto\VoIP\AuthKeyHandler;
    use Ads;
    use Button;
    use DialogHandler;
    use Events;
    use Login;
    use Loop;
    use Start;
    use Templates;
    use TOS;
    use DbPropertiesTrait;
    private const MAX_ENTITY_LENGTH = 100;
    private const MAX_ENTITY_SIZE = 8110;
    private const RSA_KEYS = [
        "-----BEGIN RSA PUBLIC KEY-----\n".
        "MIIBCgKCAQEA6LszBcC1LGzyr992NzE0ieY+BSaOW622Aa9Bd4ZHLl+TuFQ4lo4g\n".
        "5nKaMBwK/BIb9xUfg0Q29/2mgIR6Zr9krM7HjuIcCzFvDtr+L0GQjae9H0pRB2OO\n".
        "62cECs5HKhT5DZ98K33vmWiLowc621dQuwKWSQKjWf50XYFw42h21P2KXUGyp2y/\n".
        "+aEyZ+uVgLLQbRA1dEjSDZ2iGRy12Mk5gpYc397aYp438fsJoHIgJ2lgMv5h7WY9\n".
        "t6N/byY9Nw9p21Og3AoXSL2q/2IJ1WRUhebgAdGVMlV1fkuOQoEzR7EdpqtQD9Cs\n".
        "5+bfo3Nhmcyvk5ftB0WkJ9z6bNZ7yxrP8wIDAQAB\n".
        '-----END RSA PUBLIC KEY-----',
    ];
    private const TEST_RSA_KEYS = [
        "-----BEGIN RSA PUBLIC KEY-----\n".
        "MIIBCgKCAQEAyMEdY1aR+sCR3ZSJrtztKTKqigvO/vBfqACJLZtS7QMgCGXJ6XIR\n".
        "yy7mx66W0/sOFa7/1mAZtEoIokDP3ShoqF4fVNb6XeqgQfaUHd8wJpDWHcR2OFwv\n".
        "plUUI1PLTktZ9uW2WE23b+ixNwJjJGwBDJPQEQFBE+vfmH0JP503wr5INS1poWg/\n".
        "j25sIWeYPHYeOrFp/eXaqhISP6G+q2IeTaWTXpwZj4LzXq5YOpk4bYEQ6mvRq7D1\n".
        "aHWfYmlEGepfaYR8Q0YqvvhYtMte3ITnuSJs171+GDqpdKcSwHnd6FudwGO4pcCO\n".
        "j4WcDuXc2CTHgH8gFTNhp/Y8/SpDOhvn9QIDAQAB\n".
        '-----END RSA PUBLIC KEY-----',
    ];
    /**
     * Internal version of MadelineProto.
     *
     * Increased every time the default settings array or something big changes
     *
     * @internal
     * @var int
     */
    const V = 162;
    /**
     * Release version.
     *
     * @var string
     */
    const RELEASE = '7.0';
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
     * @var array
     */
    const BAD_MSG_ERROR_CODES = [16 => 'msg_id too low (most likely, client time is wrong; it would be worthwhile to synchronize it using msg_id notifications and re-send the original message with the correct msg_id or wrap it in a container with a new msg_id if the original message had waited too long on the client to be transmitted)', 17 => 'msg_id too high (similar to the previous case, the client time has to be synchronized, and the message re-sent with the correct msg_id)', 18 => 'incorrect two lower order msg_id bits (the server expects client message msg_id to be divisible by 4)', 19 => 'container msg_id is the same as msg_id of a previously received message (this must never happen)', 20 => 'message too old, and it cannot be verified whether the server has received a message with this msg_id or not', 32 => 'msg_seqno too low (the server has already received a message with a lower msg_id but with either a higher or an equal and odd seqno)', 33 => 'msg_seqno too high (similarly, there is a message with a higher msg_id but with either a lower or an equal and odd seqno)', 34 => 'an even msg_seqno expected (irrelevant message), but odd received', 35 => 'odd msg_seqno expected (relevant message), but even received', 48 => 'incorrect server salt (in this case, the bad_server_salt response is received with the correct salt, and the message is to be re-sent with it)', 64 => 'invalid container'];
    /**
     * Localized message info flags.
     *
     * @internal
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
     * Whether to generate all usernames.
     */
    const INFO_TYPE_USERNAMES = 4;
    /**
     * @internal
     */
    const BOTAPI_PARAMS_CONVERSION = ['disable_web_page_preview' => 'no_webpage', 'disable_notification' => 'silent', 'reply_to_message_id' => 'reply_to_msg_id', 'chat_id' => 'peer', 'text' => 'message'];
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
     * @var array<self>
     */
    public static array $references = [];
    /**
     * Instance of wrapper API.
     *
     */
    public APIWrapper $wrapper;
    /**
     * Settings object.
     *
     */
    public Settings $settings;
    /**
     * Config array.
     *
     */
    private array $config = ['expires' => -1];
    /**
     * TOS info.
     *
     */
    private array $tos = ['expires' => 0, 'accepted' => true];
    /**
     * Whether we're initing authorization.
     *
     */
    private bool $initing_authorization = false;
    /**
     * Authorization info (User).
     *
     */
    public ?array $authorization = null;
    /**
     * Whether we're authorized.
     *
     * @var self::NOT_LOGGED_IN|self::WAITING_*|self::LOGGED_IN
     */
    public int $authorized = self::NOT_LOGGED_IN;
    /**
     * Main authorized DC ID.
     *
     */
    public int $authorized_dc = -1;
    /**
     * RSA keys.
     *
     * @var array<RSA>
     */
    private array $rsa_keys = [];
    /**
     * RSA keys.
     *
     * @var array<RSA>
     */
    private array $test_rsa_keys = [];
    /**
     * CDN RSA keys.
     *
     */
    private array $cdn_rsa_keys = [];
    /**
     * Diffie-hellman config.
     *
     */
    private array $dh_config = ['version' => 0];
    /**
     * Internal peer database.
     *
     */
    public DbArray $chats;

    /**
     * Cache of usernames for chats.
     *
     */
    public DbArray $usernames;
    /**
     * Cached parameters for fetching channel participants.
     *
     */
    public DbArray $channelParticipants;
    /**
     * When we last stored data in remote peer database (now doesn't exist anymore).
     *
     */
    public int $last_stored = 0;
    /**
     * Temporary array of data to be sent to remote peer database.
     *
     */
    public array $qres = [];
    /**
     * Full chat info database.
     *
     */
    public DbArray $full_chats;
    /**
     * Sponsored message database.
     *
     */
    public DbArray $sponsoredMessages;
    /**
     * Latest chat message ID map for update handling.
     *
     */
    private array $msg_ids = [];
    /**
     * Version integer for upgrades.
     *
     */
    private int $v = 0;
    /**
     * Cached getdialogs params.
     *
     */
    private array $dialog_params = ['limit' => 0, 'offset_date' => 0, 'offset_id' => 0, 'offset_peer' => ['_' => 'inputPeerEmpty'], 'count' => 0];
    /**
     * Support user ID.
     *
     */
    private int $supportUser = 0;
    /**
     * File reference database.
     *
     */
    public ?ReferenceDatabase $referenceDatabase = null;
    /**
     * min database.
     *
     */
    public MinDatabase $minDatabase;
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
     * @var array<FeedLoop>
     */
    public array $feeders = [];
    /**
     * Secret chat feeder loops.
     *
     * @var array<SecretFeedLoop>
     */
    public array $secretFeeders = [];
    /**
     * Updater loops.
     *
     * @var array<UpdateLoop>
     */
    public array $updaters = [];
    /**
     * DataCenter instance.
     *
     */
    public DataCenter $datacenter;
    /**
     * Logger instance.
     *
     */
    public Logger $logger;
    /**
     * TL serializer.
     *
     */
    private TL $TL;

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
        ],
    ];

    /**
     * Nullcache array for storing main session file to DB.
     */
    public DbArray $session;

    /**
     * List of properties stored in database (memory or external).
     *
     * @see DbPropertiesFactory
     */
    protected static array $dbProperties = [
        'chats' => 'array',
        'full_chats' => 'array',
        'sponsoredMessages' => 'array',
        'channelParticipants' => 'array',
        'usernames' => 'array',
        'session' => [
            'type' => 'array',
            'config' => ['enableCache' => false],
        ],
    ];

    /**
     * Serialize session, returning object to serialize to db.
     *
     * @internal
     */
    public function serializeSession(object $data)
    {
        if (!isset($this->session) || !$this->session || $this->session instanceof MemoryArray) {
            return $data;
        }
        $this->session['data'] = $data;
        return $this->session;
    }

    /**
     * @internal
     * @return array<RSA>
     */
    public function getRsaKeys(bool $test, bool $cdn): array
    {
        if ($cdn) {
            return $this->cdn_rsa_keys;
        }
        if ($test) {
            return $this->test_rsa_keys;
        }
        return $this->rsa_keys;
    }
    /**
     * Serialize all instances.
     *
     * CALLED ONLY ON SHUTDOWN.
     *
     * @internal
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
            $instance->wrapper->serialize();
        }
        Logger::log('Done final serialization (SHUTDOWN)!');
    }

    private ?Future $initPromise = null;

    /**
     * Constructor function.
     *
     * @param Settings|SettingsEmpty $settings Settings
     * @param null|APIWrapper            $wrapper  API wrapper
     */
    public function __construct(Settings|SettingsEmpty $settings, ?APIWrapper $wrapper = null)
    {
        if ($wrapper) {
            self::$references[\spl_object_hash($this)] = $this;
            $this->wrapper = $wrapper;
        }

        $initDeferred = new DeferredFuture;
        $this->initPromise = $initDeferred->getFuture();
        $this->initialize($settings);
        $initDeferred->complete();
    }

    /**
     * Initialization function.
     *
     * @internal
     */
    private function initialize(Settings|SettingsEmpty $settings): void
    {
        // Initialize needed stuffs
        Magic::start();
        // Parse and store settings
        $this->updateSettingsInternal($settings, false);
        // Actually instantiate needed classes like a boss
        $this->cleanupProperties();
        // Start IPC server
        if (!$this->ipcServer) {
            try {
                $this->ipcServer = new Server($this);
                $this->ipcServer->setSettings($this->settings->getIpc());
                $this->ipcServer->setIpcPath($this->wrapper->session);
            } catch (Throwable $e) {
                $this->logger->logger("Error while starting IPC server: $e", Logger::FATAL_ERROR);
            }
        }
        try {
            $this->ipcServer->start();
        } catch (Throwable $e) {
            if (Magic::$isIpcWorker) {
                throw $e;
            }
            $this->logger->logger("Error while starting IPC server: $e", Logger::FATAL_ERROR);
        }
        // Load rsa keys
        $this->rsa_keys = [];
        foreach (self::RSA_KEYS as $key) {
            $key = RSA::load($this->TL, $key);
            $this->rsa_keys[$key->fp] = $key;
        }
        $this->test_rsa_keys = [];
        foreach (self::TEST_RSA_KEYS as $key) {
            $key = RSA::load($this->TL, $key);
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
        $this->connectToAllDcs();
        $this->startLoops();
        $this->datacenter->currentDatacenter= 2;
        if ((!isset($this->authorization['user']['bot']) || !$this->authorization['user']['bot']) && $this->datacenter->getDataCenterConnection($this->datacenter->currentDatacenter)->hasTempAuthKey()) {
            try {
                $nearest_dc = $this->methodCallAsyncRead('help.getNearestDc', []);
                $this->logger->logger(\sprintf(Lang::$current_lang['nearest_dc'], $nearest_dc['country'], $nearest_dc['nearest_dc']), Logger::NOTICE);
                if ($nearest_dc['nearest_dc'] != $nearest_dc['this_dc']) {
                    $this->authorized_dc = $this->datacenter->currentDatacenter = (int) $nearest_dc['nearest_dc'];
                }
            } catch (RPCErrorException $e) {
                if ($e->rpc !== 'BOT_METHOD_INVALID') {
                    throw $e;
                }
            }
        }
        $this->getConfig();
        $this->startUpdateSystem(true);
        $this->v = self::V;

        $this->settings->applyChanges();
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
     * @internal
     */
    public function __sleep(): array
    {
        return [
            // Databases
            'chats',
            'full_chats',
            'referenceDatabase',
            'minDatabase',
            'channelParticipants',
            'usernames',
            'sponsoredMessages',

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
            'updates',
            'updates_key',
            'webhookUrl',

            'updateHandlerType',

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
    }

    private function fillUsernamesCache(): void
    {
        if (!$this->settings->getDb()->getEnableUsernameDb()) {
            $this->usernames->clear();
            return;
        }
        if (!isset($this->usernames[''])) {
            $this->logger('Filling database cache. This can take a few minutes.', Logger::WARNING);
            foreach ($this->chats as $id => $chat) {
                if (isset($chat['username'])) {
                    $this->usernames[\strtolower($chat['username'])] = $id;
                }
                foreach ($chat['usernames'] ?? [] as ['username' => $username]) {
                    $this->usernames[\strtolower($username)] = $id;
                }
            }
            $this->usernames[''] = 0;
            $this->logger('Cache filled.', Logger::WARNING);
        }
    }

    /**
     * Logger.
     *
     * @param mixed  $param Parameter
     * @param int    $level Logging level
     * @param string $file  File where the message originated
     */
    public function logger(mixed $param, int $level = Logger::NOTICE, string $file = ''): void
    {
        if ($file === null) {
            $file = \basename(\debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'], '.php');
        }
        isset($this->logger) ? $this->logger->logger($param, $level, $file) : Logger::$default->logger($param, $level, $file);
    }
    /**
     * Get TL namespaces.
     */
    public function getMethodNamespaces(): array
    {
        return $this->TL->getMethodNamespaces();
    }
    /**
     * Get namespaced methods (method => namespace).
     */
    public function getMethodsNamespaced(): array
    {
        return $this->TL->getMethodsNamespaced();
    }
    /**
     * Get TL serializer.
     */
    public function getTL(): TL
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
     */
    public function getHTTPClient(): HttpClient
    {
        return $this->datacenter->getHTTPClient();
    }
    /**
     * Get async DNS client.
     */
    public function getDNSClient(): DnsResolver
    {
        return $this->datacenter->getDNSClient();
    }
    /**
     * Get contents of remote file asynchronously.
     *
     * @param string $url URL
     */
    public function fileGetContents(string $url): string
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
     */
    public function getDataCenterId(): int|string
    {
        return $this->datacenter->currentDatacenter;
    }
    /**
     * Prompt serialization of instance.
     *
     * @internal
     */
    public function serialize(): void
    {
        if (isset($this->wrapper) && $this->isInited()) {
            $this->wrapper->serialize();
        }
    }
    /**
     * Start all internal loops.
     */
    private function startLoops(): void
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
        if (!$this->configLoop) {
            $this->configLoop = new PeriodicLoopInternal($this, [$this, 'getConfig'], 'config', 24 * 3600 * 1000);
        }
        if (!$this->ipcServer) {
            try {
                $this->ipcServer = new Server($this);
                $this->ipcServer->setSettings($this->settings->getIpc());
                $this->ipcServer->setIpcPath($this->wrapper->session);
            } catch (Throwable $e) {
                $this->logger->logger("Error while starting IPC server: $e", Logger::FATAL_ERROR);
            }
        }
        $this->callCheckerLoop->start();
        $this->serializeLoop->start();
        $this->phoneConfigLoop->start();
        $this->configLoop->start();
        try {
            $this->ipcServer->start();
        } catch (Throwable $e) {
            if (Magic::$isIpcWorker) {
                throw $e;
            }
            $this->logger->logger("Error while starting IPC server: $e", Logger::FATAL_ERROR);
        }
    }
    /**
     * Stop all internal loops.
     */
    private function stopLoops(): void
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
        if ($this->ipcServer) {
            $this->ipcServer->signal(null);
            $this->ipcServer = null;
        }
    }
    /**
     * Clean up properties from previous versions of MadelineProto.
     *
     * @internal
     */
    private function cleanupProperties(): void
    {
        $this->channels_state ??= new CombinedUpdatesState;
        $this->datacenter ??= new DataCenter($this, $this->dcList, $this->settings->getConnection());
        $this->snitch ??= new Snitch;

        $this->referenceDatabase ??= new ReferenceDatabase($this);
        $this->minDatabase ??= new MinDatabase($this);

        $db = [];
        $db []= async($this->referenceDatabase->init(...));
        $db []= async($this->minDatabase->init(...));
        $db []= async($this->initDb(...), $this);
        await($db);

        if (!isset($this->TL)) {
            $this->TL = new TL($this);
            $callbacks = [$this, $this->referenceDatabase];
            if (!($this->authorization['user']['bot'] ?? false)) {
                $callbacks[] = $this->minDatabase;
            }
            $this->TL->init($this->settings->getSchema(), $callbacks);
        }

        $this->fillUsernamesCache();

        if (!$this->settings->getDb()->getEnableFullPeerDb()) {
            $this->full_chats->clear();
        }
        if (!$this->settings->getDb()->getEnablePeerInfoDb()) {
            if (isset($this->chats[0])) {
                return;
            }
            $this->logger('Cleaning up peer database...');
            $k = 0;
            $total = \count($this->chats);
            foreach ($this->chats as $key => $value) {
                $value = [
                    '_' => $value['_'],
                    'id' => $value['id'],
                    'access_hash' => $value['access_hash'] ?? null,
                    'min' => $value['min'] ?? false,
                ];
                $k++;
                if ($k % 500 === 0 || $k === $total) {
                    $this->logger("Cleaning up peer database ($k/$total)...");
                    $this->chats->set($key, $value);
                } else {
                    $this->chats->set($key, $value);
                }
            }
            $this->chats->set(0, []);
            $this->logger('Cleaned up peer database!');
        } elseif (isset($this->chats[0])) {
            unset($this->chats[0]);
        }
    }

    /**
     * Upgrade MadelineProto instance.
     */
    private function upgradeMadelineProto(): void
    {
        if (isset($this->hook_url) && \is_string($this->hook_url)) {
            $this->webhookUrl = $this->hook_url;
        }

        $this->logger->logger(Lang::$current_lang['serialization_ofd'], Logger::WARNING);
        foreach ($this->datacenter->getDataCenterConnections() as $dc_id => $socket) {
            if ($this->authorized === self::LOGGED_IN && \is_int($dc_id) && $socket->hasPermAuthKey() && $socket->hasTempAuthKey()) {
                $socket->bind();
                $socket->authorized(true);
            }
        }
        $this->settings->setSchema(new TLSchema);
        $this->usernames->clear();

        $this->resetMTProtoSession(true, true);
        $this->config = ['expires' => -1];
        $this->dh_config = ['version' => 0];
        $this->initialize($this->settings);
        foreach ($this->secret_chats as $chat => $data) {
            try {
                if (isset($this->secret_chats[$chat]) && $this->secret_chats[$chat]['InputEncryptedChat'] !== null) {
                    $this->notifyLayer($chat);
                }
            } catch (RPCErrorException $e) {
            }
        }
    }
    /**
     * Post-deserialization initialization function.
     *
     * @param Settings|SettingsEmpty $settings New settings
     * @param APIWrapper             $wrapper  API wrapper
     * @internal
     */
    public function wakeup(SettingsAbstract $settings, APIWrapper $wrapper): void
    {
        // Setup one-time stuffs
        Magic::start();

        // Set reference to itself
        self::$references[\spl_object_hash($this)] = $this;
        // Set API wrapper
        $this->wrapper = $wrapper;

        $deferred = new DeferredFuture;
        $this->initPromise = $deferred->getFuture();

        // Cleanup old properties, init new stuffs
        $this->cleanupProperties();

        // Re-set TL closures
        $callbacks = [$this];
        if ($this->settings->getDb()->getEnableFileReferenceDb()) {
            $callbacks []= $this->referenceDatabase;
        }
        if (!($this->authorization['user']['bot'] ?? false) && $this->settings->getDb()->getEnableMinDb()) {
            $callbacks[] = $this->minDatabase;
        }

        $this->TL->updateCallbacks($callbacks);

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
        $this->updateSettings($settings);
        // Session update process for BC
        $forceDialogs = false;
        if (!isset($this->v)
            || $this->v !== self::V
            || $this->settings->getSchema()->needsUpgrade()) {
            $this->upgradeMadelineProto();
            $forceDialogs = true;
        }
        // Update TL callbacks
        $callbacks = [$this];
        if ($this->settings->getDb()->getEnableFileReferenceDb()) {
            $callbacks[] = $this->referenceDatabase;
        }
        if ($this->settings->getDb()->getEnableMinDb() && !($this->authorization['user']['bot'] ?? false)) {
            $callbacks[] = $this->minDatabase;
        }
        // Connect to all DCs, start internal loops
        $this->connectToAllDcs();
        if ($this->fullGetSelf()) {
            $this->authorized = self::LOGGED_IN;
            $this->setupLogger();
            $this->startLoops();
            $this->getCdnConfig($this->datacenter->currentDatacenter);
            $this->initAuthorization();
        } else {
            $this->startLoops();
        }
        // onStart event handler
        if ($this->event_handler && \class_exists($this->event_handler) && \is_subclass_of($this->event_handler, EventHandler::class)) {
            $this->setEventHandler($this->event_handler);
        }
        $this->startUpdateSystem(true);
        if ($this->authorized === self::LOGGED_IN && !$this->authorization['user']['bot'] && $this->settings->getPeer()->getCacheAllPeersOnStartup()) {
            $this->getDialogs($forceDialogs);
        }
        if ($this->authorized === self::LOGGED_IN) {
            $this->logger->logger(Lang::$current_lang['getupdates_deserialization'], Logger::NOTICE);
            $this->updaters[UpdateLoop::GENERIC]->resume();
        }
        $this->updaters[UpdateLoop::GENERIC]->start();

        $deferred->complete();
    }
    /**
     * Unreference instance, allowing destruction.
     *
     * @internal
     */
    public function unreference(): void
    {
        if (!isset($this->logger)) {
            $this->logger = new Logger(new \danog\MadelineProto\Settings\Logger);
        }
        $this->logger->logger('Will unreference instance');
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
        $this->logger->logger('Unreferenced instance');
    }
    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->logger('Shutting down MadelineProto (MTProto)');
        $this->unreference();
        $this->logger('Successfully destroyed MadelineProto');
    }
    /**
     * @internal
     */
    public function isInited(): bool
    {
        return $this->initPromise?->isComplete() ?? false;
    }
    /**
     * @internal
     */
    public function waitForInit(): void
    {
        $this->initPromise?->await();
    }
    /**
     * Whether we're an IPC client instance.
     */
    public function isIpc(): bool
    {
        return false;
    }
    /**
     * Whether we're an IPC server process (as opposed to an event handler).
     */
    public function isIpcWorker(): bool
    {
        return Magic::$isIpcWorker;
    }
    /**
     * Parse, update and store settings.
     *
     * @param SettingsAbstract $settings Settings
     */
    public function updateSettings(SettingsAbstract $settings): void
    {
        $this->updateSettingsInternal($settings);
    }
    /**
     * Parse, update and store settings.
     *
     * @param SettingsAbstract $settings Settings
     */
    private function updateSettingsInternal(SettingsAbstract $settings, bool $recurse = true): void
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
            throw new Exception(Lang::$current_lang['api_not_set'], 0, null, 'MadelineProto', 1);
        }

        // Setup logger
        if ($this->settings->getLogger()->hasChanged() || !isset($this->logger)) {
            $this->setupLogger();
        }

        if ($this->settings->getDb()->hasChanged()) {
            $this->logger->logger("The database settings have changed!", Logger::WARNING);
            $this->cleanupProperties();
            $this->settings->getDb()->applyChanges();
        }
        if ($this->settings->getIpc()->hasChanged()) {
            $this->logger->logger("The IPC settings have changed!", Logger::WARNING);
            if (isset($this->ipcServer)) {
                $this->ipcServer->setSettings($this->settings->getIpc()->applyChanges());
            }
        }
        if ($this->settings->getSerialization()->hasChanged()) {
            $this->logger->logger("The serialization settings have changed!", Logger::WARNING);
            if (isset($this->serializeLoop)) {
                $this->serializeLoop->signal(true);
            }
            $this->serializeLoop = new PeriodicLoopInternal($this, [$this, 'serialize'], 'serialize', $this->settings->getSerialization()->applyChanges()->getInterval() * 1000);
            $this->serializeLoop->start();
        }
        if ($recurse && ($this->settings->getAuth()->hasChanged()
            || $this->settings->getConnection()->hasChanged()
            || $this->settings->getSchema()->hasChanged()
            || $this->settings->getSchema()->needsUpgrade())) {
            $this->logger->logger("Generic settings have changed!", Logger::WARNING);
            $this->initialize($this->settings);
        }
    }
    /**
     * Return current settings.
     */
    public function getSettings(): Settings
    {
        return $this->settings;
    }
    /**
     * Setup logger.
     */
    public function setupLogger(): void
    {
        $this->logger = new Logger(
            $this->settings->getLogger(),
            (string) ($this->authorization['user']['username'] ?? $this->authorization['user']['id'] ?? ''),
        );
    }
    /**
     * Reset all MTProto sessions.
     *
     * @param boolean $de       Whether to reset the session ID
     * @param boolean $auth_key Whether to reset the auth key
     * @internal
     */
    public function resetMTProtoSession(bool $de = true, bool $auth_key = false): void
    {
        if (!\is_object($this->datacenter)) {
            throw new Exception(Lang::$current_lang['session_corrupted']);
        }
        foreach ($this->datacenter->getDataCenterConnections() as $socket) {
            if ($de) {
                $socket->resetSession();
            }
            if ($auth_key) {
                $socket->setTempAuthKey(null);
            }
        }
    }
    /**
     * Check if connected to datacenter using HTTP.
     *
     * @param int $datacenter DC ID
     * @internal
     */
    public function isHttp(int $datacenter): bool
    {
        return $this->datacenter->isHttp($datacenter);
    }
    /**
     * Checks whether all datacenters are authorized.
     */
    public function hasAllAuth(): bool
    {
        if ($this->isInitingAuthorization()) {
            $this->logger('Initing auth');
            return false;
        }
        foreach ($this->datacenter->getDataCenterConnections() as $id => $dc) {
            if ((!$dc->isAuthorized() || !$dc->hasTempAuthKey()) && !$dc->isCDN()) {
                $this->logger("Initing auth $id");
                return false;
            }
        }
        return true;
    }
    /**
     * Whether we're initing authorization.
     *
     * @internal
     */
    public function isInitingAuthorization(): bool
    {
        return $this->initing_authorization;
    }
    /**
     * Connects to all datacenters and if necessary creates authorization keys, binds them and writes client info.
     *
     * @param boolean $reconnectAll Whether to reconnect to all DCs
     */
    public function connectToAllDcs(bool $reconnectAll = true): void
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
        foreach ($this->datacenter->getDcs(true) as $new_dc) {
            if (!\is_int($new_dc)) {
                continue;
            }
            $dcs[] = async($this->datacenter->dcConnect(...), $new_dc);
        }
        await($dcs);
        $this->initAuthorization();
        $this->parseConfig();
        $dcs = [];
        foreach ($this->datacenter->getDcs(false) as $new_dc) {
            if (!\is_int($new_dc)) {
                continue;
            }
            $dcs[] = async($this->datacenter->dcConnect(...), $new_dc);
        }
        await($dcs);
        $this->initAuthorization();
        $this->parseConfig();
        $this->getPhoneConfig();
    }
    /**
     * Reset the update state and fetch all updates from the beginning.
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
     * @internal
     */
    public function startUpdateSystem(bool $anyway = false): void
    {
        if (!$this->isInited() && !$anyway) {
            $this->logger('Not starting update system');
            return;
        }
        $this->logger('Starting update system');
        foreach ($this->secret_chats as $id => $chat) {
            if (!isset($this->secretFeeders[$id])) {
                $this->secretFeeders[$id] = new SecretFeedLoop($this, $id);
            }
            $this->secretFeeders[$id]->start();
            if (isset($this->secretFeeders[$id])) {
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
            $this->feeders[$channelId]->start();
            if (isset($this->feeders[$channelId])) {
                $this->feeders[$channelId]->resume();
            }
            $this->updaters[$channelId]->start();
            if (isset($this->updaters[$channelId])) {
                $this->updaters[$channelId]->resume();
            }
        }
        $this->flushAll();
        $this->seqUpdater->start();
        $this->seqUpdater->resume();
    }
    /**
     * Flush all datacenter connections.
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
     * @internal
     */
    public function getPhoneConfig(mixed $watcherId = null): void
    {
        if ($this->authorized === self::LOGGED_IN
            && \class_exists(VoIPServerConfigInternal::class)
            && !$this->authorization['user']['bot']
            && $this->datacenter->getDataCenterConnection($this->authorized_dc)->hasTempAuthKey()) {
            $this->logger->logger('Fetching phone config...');
            VoIPServerConfig::updateDefault($this->methodCallAsyncRead('phone.getCallConfig', []));
        }
    }
    /**
     * Store RSA keys for CDN datacenters.
     */
    public function getCdnConfig(): void
    {
        try {
            foreach (($this->methodCallAsyncRead('help.getCdnConfig', [], ['datacenter' => $this->authorized_dc]))['public_keys'] as $curkey) {
                $curkey = RSA::load($this->TL, $curkey['public_key']);
                $this->cdn_rsa_keys[$curkey->fp] = $curkey;
            }
        } catch (\danog\MadelineProto\TL\Exception $e) {
            $this->logger->logger($e->getMessage(), Logger::FATAL_ERROR);
        }
    }
    /**
     * Get cached server-side config.
     */
    public function getCachedConfig(): array
    {
        return $this->config;
    }
    /**
     * Get cached (or eventually re-fetch) server-side config.
     *
     * @param array $config  Current config
     */
    public function getConfig(array $config = []): array
    {
        if ($this->config['expires'] > \time()) {
            return $this->config;
        }
        $this->config = empty($config) ? $this->methodCallAsyncRead('help.getConfig', $config) : $config;
        $this->parseConfig();
        $this->logger->logger(Lang::$current_lang['config_updated'], Logger::NOTICE);
        $this->logger->logger($this->config, Logger::NOTICE);
        return $this->config;
    }
    /**
     * @internal
     */
    public function addConfig(array $config): void
    {
        $this->config = $config;
    }
    /**
     * Parse cached config.
     */
    private function parseConfig(): void
    {
        if (isset($this->config['dc_options'])) {
            $options = $this->config['dc_options'];
            unset($this->config['dc_options']);
            $this->parseDcOptions($options);
        }
    }
    /**
     * Parse DC options from config.
     *
     * @param array $dc_options DC options
     */
    private function parseDcOptions(array $dc_options): void
    {
        $new = [];
        foreach ($dc_options as $dc) {
            if ($dc['static']) {
                continue;
            }

            $test = $this->config['test_mode'] ? 'test' : 'main';
            $id = $dc['id'];
            if ($this->config['test_mode']) {
                $id += 10000;
            }
            if ($dc['media_only']) {
                $id = -$id;
            }
            $ipv6 = $dc['ipv6'] ? 'ipv6' : 'ipv4';
            unset($dc['media_only'], $dc['id'], $dc['ipv6']);
            $new[$test][$ipv6][$id] = $dc;
        }
        $previous = $this->dcList;
        $this->dcList = $new;
        $currentDatacenter = $this->datacenter->currentDatacenter;
        if ($previous !== $this->dcList && (!$this->datacenter->has($currentDatacenter) || $this->datacenter->getDataCenterConnection($currentDatacenter)->byIPAddress())) {
            $this->logger->logger('Got new DC options, reconnecting');
            $this->connectToAllDcs(false);
        }
        $this->datacenter->currentDatacenter = $currentDatacenter;
    }
    /**
     * Get info about the logged-in user, cached.
     *
     * Use fullGetSelf to bypass the cache.
     */
    public function getSelf(): array|false
    {
        return $this->authorization['user'] ?? false;
    }
    /**
     * Returns whether the current user is a premium user, cached.
     */
    public function isPremium(): bool
    {
        return $this->getSelf()['premium'];
    }
    /**
     * Get info about the logged-in user, not cached.
     */
    public function fullGetSelf(): array|false
    {
        try {
            $this->authorization = ['user' => ($this->methodCallAsyncRead('users.getUsers', ['id' => [['_' => 'inputUserSelf']]]))[0]];
        } catch (RPCErrorException $e) {
            $this->logger->logger($e->getMessage());
            return false;
        }
        return $this->authorization['user'];
    }
    /**
     * Get authorization info.
     */
    public function getAuthorization(): int
    {
        return $this->authorized;
    }
    /**
     * Get current password hint.
     */
    public function getHint(): string
    {
        if ($this->authorized !== self::WAITING_PASSWORD) {
            throw new Exception('Not waiting for the password!');
        }
        Assert::string($this->authorization['hint']);
        return $this->authorization['hint'];
    }
    /**
     * IDs of peers where to report errors.
     *
     * @var array<int>
     */
    private array $reportDest = [];
    /**
     * Check if has report peers.
     */
    public function hasReportPeers(): bool
    {
        return (bool) $this->reportDest;
    }
    /**
     * Get a message to show to the user when starting the bot.
     */
    public function getWebMessage(string $message): string
    {
        Logger::log($message);

        $warning = '';
        if (!$this->hasReportPeers() && $this->hasEventHandler()) {
            Logger::log('!!! Warning: no report peers are set, please add the following method to your event handler !!!', Logger::FATAL_ERROR);
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
     * @param int|string|array<int|string> $userOrId Username(s) or peer ID(s)
     */
    public function setReportPeers(int|string|array $userOrId): void
    {
        if (!(\is_array($userOrId) && !isset($userOrId['_']) && !isset($userOrId['id']))) {
            $userOrId = [$userOrId];
        }
        foreach ($userOrId as $k => &$peer) {
            try {
                $peer = ($this->getInfo($peer))['bot_api_id'];
                if ($peer === 101374607) {
                    unset($userOrId[$k]);
                }
            } catch (Throwable $e) {
                unset($userOrId[$k]);
                $this->logger("Could not obtain info about report peer $peer: $e", Logger::FATAL_ERROR);
            }
        }
        /** @var array<int> $userOrId */
        $this->reportDest = $userOrId;
    }
    private ?LocalMutex $reportMutex = null;
    /**
     * Report an error to the previously set peer.
     *
     * @param string $message   Error to report
     * @param string $parseMode Parse mode
     */
    public function report(string $message, string $parseMode = ''): void
    {
        if (!$this->reportDest) {
            return;
        }
        $this->reportMutex ??= new LocalMutex;
        $lock = $this->reportMutex->acquire();
        try {
            $file = null;
            if ($this->settings->getLogger()->getType() === Logger::FILE_LOGGER
                && $path = $this->settings->getLogger()->getExtra()) {
                touchAsync($path);
                if (!getSize($path)) {
                    $message = "!!! WARNING !!!\nThe logfile is empty, please DO NOT delete the logfile to avoid errors in MadelineProto!\n\n$message";
                } else {
                    $file = $this->methodCallAsyncRead(
                        'messages.uploadMedia',
                        [
                            'peer' => $this->reportDest[0],
                            'media' => [
                                '_' => 'inputMediaUploadedDocument',
                                'file' => $path,
                                'attributes' => [
                                    ['_' => 'documentAttributeFilename', 'file_name' => 'MadelineProto.log'],
                                ],
                            ],
                        ],
                    );
                }
            }
            $sent = false;
            foreach ($this->reportDest as $id) {
                try {
                    $this->methodCallAsyncRead('messages.sendMessage', ['peer' => $id, 'message' => $message, 'parse_mode' => $parseMode]);
                    if ($file) {
                        $this->methodCallAsyncRead('messages.sendMedia', ['peer' => $id, 'media' => $file]);
                    }
                    $sent = true;
                } catch (Throwable $e) {
                    $this->logger("While reporting to $id: $e", Logger::FATAL_ERROR);
                }
            }
            if ($sent && $file) {
                $this->logger->truncate();
                $this->logger->logger('Reported!');
            }
        } finally {
            $lock->release();
        }
    }
    /**
     * Get full list of MTProto and API methods.
     */
    public function getAllMethods(): array
    {
        $methods = [];
        foreach ($this->getTL()->getMethods()->by_id as $method) {
            $methods[] = $method['method'];
        }
        return \array_merge($methods, \get_class_methods(InternalDoc::class));
    }
    public function getMethodAfterResponseDeserializationCallbacks(): array
    {
        return [];
    }
    public function getMethodBeforeResponseDeserializationCallbacks(): array
    {
        return [];
    }
    public function getConstructorAfterDeserializationCallbacks(): array
    {
        return \array_merge(
            \array_fill_keys(['chat', 'chatEmpty', 'chatForbidden', 'channel', 'channelEmpty', 'channelForbidden'], [$this->addChat(...)]),
            \array_fill_keys(['user', 'userEmpty'], [$this->addUser(...)]),
            \array_fill_keys(['chatFull', 'channelFull', 'userFull'], [$this->addFullChat(...)]),
            ['help.support' => [$this->addSupport(...)]],
            ['config' => [$this->addConfig(...)]],
        );
    }
    public function getConstructorBeforeDeserializationCallbacks(): array
    {
        return [];
    }
    public function getConstructorBeforeSerializationCallbacks(): array
    {
        return [];
    }
    public function getTypeMismatchCallbacks(): array
    {
        return \array_merge(
            \array_fill_keys(
                [
                    'InputPeer',
                ],
                $this->getInputPeer(...),
            ),
            \array_fill_keys(
                [
                    'InputUser',
                    'InputChannel',
                ],
                $this->getInputConstructor(...),
            ),
            \array_fill_keys(
                [
                    'User',
                    'Chat',
                    'Peer',
                    'InputDialogPeer',
                    'InputNotifyPeer',
                ],
                $this->getInfo(...),
            ),
            \array_fill_keys(
                [
                    'InputMedia',
                    'InputDocument',
                    'InputPhoto',
                ],
                $this->getFileInfo(...),
            ),
            \array_fill_keys(
                ['InputFileLocation'],
                $this->getDownloadInfo(...),
            ),
        );
    }
    public function areDeserializationCallbacksMutuallyExclusive(): bool
    {
        return false;
    }
    /**
     * Get debug information for var_dump.
     */
    public function __debugInfo(): array
    {
        $vars = \get_object_vars($this);
        unset($vars['full_chats'], $vars['chats'], $vars['referenceDatabase'], $vars['minDatabase'], $vars['TL']);
        return $vars;
    }
}
