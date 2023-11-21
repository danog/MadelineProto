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

use Amp\ByteStream\ReadableStream;
use Amp\Cache\Cache;
use Amp\Cache\LocalCache;
use Amp\Cancellation;
use Amp\DeferredFuture;
use Amp\Dns\DnsResolver;
use Amp\Future;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\Request;
use Amp\Sync\LocalKeyedMutex;
use Amp\Sync\LocalMutex;
use AssertionError;
use danog\MadelineProto\Broadcast\Broadcast;
use danog\MadelineProto\Db\DbArray;
use danog\MadelineProto\Db\DbPropertiesFactory;
use danog\MadelineProto\Db\DbPropertiesTrait;
use danog\MadelineProto\Db\MemoryArray;
use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\Ipc\Server;
use danog\MadelineProto\Loop\Generic\PeriodicLoopInternal;
use danog\MadelineProto\Loop\Update\FeedLoop;
use danog\MadelineProto\Loop\Update\SeqLoop;
use danog\MadelineProto\Loop\Update\UpdateLoop;
use danog\MadelineProto\MTProtoTools\AuthKeyHandler;
use danog\MadelineProto\MTProtoTools\CallHandler;
use danog\MadelineProto\MTProtoTools\CombinedUpdatesState;
use danog\MadelineProto\MTProtoTools\Files;
use danog\MadelineProto\MTProtoTools\MinDatabase;
use danog\MadelineProto\MTProtoTools\PasswordCalculator;
use danog\MadelineProto\MTProtoTools\PeerDatabase;
use danog\MadelineProto\MTProtoTools\PeerHandler;
use danog\MadelineProto\MTProtoTools\ReferenceDatabase;
use danog\MadelineProto\MTProtoTools\UpdateHandler;
use danog\MadelineProto\MTProtoTools\UpdatesState;
use danog\MadelineProto\Settings\Database\DriverDatabaseAbstract;
use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\TL\Conversion\BotAPI;
use danog\MadelineProto\TL\Conversion\BotAPIFiles;
use danog\MadelineProto\TL\Conversion\TD;
use danog\MadelineProto\TL\TL;
use danog\MadelineProto\TL\TLCallback;
use danog\MadelineProto\TL\TLInterface;
use danog\MadelineProto\TL\Types\LoginQrCode;
use danog\MadelineProto\VoIP\CallState;
use danog\MadelineProto\Wrappers\Ads;
use danog\MadelineProto\Wrappers\Button;
use danog\MadelineProto\Wrappers\DialogHandler;
use danog\MadelineProto\Wrappers\Events;
use danog\MadelineProto\Wrappers\Login;
use danog\MadelineProto\Wrappers\Loop;
use danog\MadelineProto\Wrappers\Start;
use Psr\Log\LoggerInterface;
use Revolt\EventLoop;
use Throwable;
use Webmozart\Assert\Assert;

use function Amp\async;
use function Amp\File\deleteFile;
use function Amp\File\getSize;
use function Amp\File\openFile;
use function Amp\File\read;
use function Amp\Future\await;

use function time;

/**
 * Manages all of the mtproto stuff.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @internal
 */
final class MTProto implements TLCallback, LoggerGetter, SettingsGetter
{
    use AuthKeyHandler;
    use CallHandler;
    use PeerHandler;
    use UpdateHandler;
    use Files;
    use \danog\MadelineProto\SecretChats\AuthKeyHandler;
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
    use DbPropertiesTrait;
    use Broadcast;
    private const MAX_ENTITY_LENGTH = 100;
    private const MAX_ENTITY_SIZE = 8110;
    /**
     * Internal version of MadelineProto.
     *
     * Increased every time the default settings array or something big changes
     *
     * @internal
     * @var int
     */
    public const V = 177;
    /**
     * Bad message error codes.
     *
     * @internal
     * @var array
     */
    public const BAD_MSG_ERROR_CODES = [16 => 'msg_id too low (most likely, client time is wrong; it would be worthwhile to synchronize it using msg_id notifications and re-send the original message with the correct msg_id or wrap it in a container with a new msg_id if the original message had waited too long on the client to be transmitted)', 17 => 'msg_id too high (similar to the previous case, the client time has to be synchronized, and the message re-sent with the correct msg_id)', 18 => 'incorrect two lower order msg_id bits (the server expects client message msg_id to be divisible by 4)', 19 => 'container msg_id is the same as msg_id of a previously received message (this must never happen)', 20 => 'message too old, and it cannot be verified whether the server has received a message with this msg_id or not', 32 => 'msg_seqno too low (the server has already received a message with a lower msg_id but with either a higher or an equal and odd seqno)', 33 => 'msg_seqno too high (similarly, there is a message with a higher msg_id but with either a lower or an equal and odd seqno)', 34 => 'an even msg_seqno expected (irrelevant message), but odd received', 35 => 'odd msg_seqno expected (relevant message), but even received', 48 => 'incorrect server salt (in this case, the bad_server_salt response is received with the correct salt, and the message is to be re-sent with it)', 64 => 'invalid container'];
    /**
     * Localized message info flags.
     *
     * @internal
     * @var array
     */
    public const MSGS_INFO_FLAGS = [1 => 'nothing is known about the message (msg_id too low, the other party may have forgotten it)', 2 => 'message not received (msg_id falls within the range of stored identifiers; however, the other party has certainly not received a message like that)', 3 => 'message not received (msg_id too high; however, the other party has certainly not received it yet)', 4 => 'message received (note that this response is also at the same time a receipt acknowledgment)', 8 => ' and message already acknowledged', 16 => ' and message not requiring acknowledgment', 32 => ' and RPC query contained in message being processed or processing already complete', 64 => ' and content-related response to message already generated', 128 => ' and other party knows for a fact that message is already received'];
    /**
     * @internal
     */
    public const TD_PARAMS_CONVERSION = ['updateNewMessage' => ['_' => 'updateNewMessage', 'disable_notification' => ['message', 'silent'], 'message' => ['message']], 'message' => ['_' => 'message', 'id' => ['id'], 'sender_user_id' => ['from_id'], 'chat_id' => ['peer_id', 'choose_chat_id_from_botapi'], 'send_state' => ['choose_incoming_or_sent'], 'can_be_edited' => ['choose_can_edit'], 'can_be_deleted' => ['choose_can_delete'], 'is_post' => ['post'], 'date' => ['date'], 'edit_date' => ['edit_date'], 'forward_info' => ['fwd_info', 'choose_forward_info'], 'reply_to_message_id' => ['reply_to_msg_id'], 'ttl' => ['choose_ttl'], 'ttl_expires_in' => ['choose_ttl_expires_in'], 'via_bot_user_id' => ['via_bot_id'], 'views' => ['views'], 'content' => ['choose_message_content'], 'reply_markup' => ['reply_markup']], 'messages.sendMessage' => ['chat_id' => ['peer'], 'reply_to_message_id' => ['reply_to_msg_id'], 'disable_notification' => ['silent'], 'from_background' => ['background'], 'input_message_content' => ['choose_message_content'], 'reply_markup' => ['reply_markup']]];
    /**
     * @internal
     */
    public const TD_REVERSE = ['sendMessage' => 'messages.sendMessage'];
    /**
     * @internal
     */
    public const TD_IGNORE = ['updateMessageID'];
    /**
     * @internal
     */
    public const BOTAPI_PARAMS_CONVERSION = ['disable_web_page_preview' => 'no_webpage', 'disable_notification' => 'silent', 'reply_to_message_id' => 'reply_to_msg_id', 'chat_id' => 'peer', 'text' => 'message'];
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
     * Authorization info (User).
     *
     */
    public ?array $authorization = null;
    /**
     * Whether we're authorized.
     *
     * @var API::NOT_LOGGED_IN|API::WAITING_*|API::LOGGED_IN|API::LOGGED_OUT
     */
    public int $authorized = API::NOT_LOGGED_IN;
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
     * Min database.
     *
     */
    public MinDatabase $minDatabase;
    /**
     * Peer database.
     *
     */
    public PeerDatabase $peerDatabase;
    /**
     * Phone config loop.
     */
    public ?PeriodicLoopInternal $phoneConfigLoop = null;
    /**
     * Config loop.
     */
    public ?PeriodicLoopInternal $configLoop = null;
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
    private ?LoginQrCode $loginQrCode = null;
    /**
     * Feeder loops.
     *
     * @var array<FeedLoop>
     */
    public array $feeders = [];
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
     */
    private TL $TL;

    private Cache $reportCache;

    /**
     * Snitch.
     */
    private Snitch $snitch;

    /**
     * DC list.
     */
    public array $dcList = [
        'test' => [
            // Test datacenters
            'ipv4' => [
                // ipv4 addresses
                10002 => [
                    // The rest will be fetched using help.getConfig
                    'ip_address' => '149.154.167.40',
                    'port' => 443,
                    'media_only' => false,
                    'tcpo_only' => false,
                ],
            ],
            'ipv6' => [
                // ipv6 addresses
                10002 => [
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
        'sponsoredMessages' => ['innerMadelineProto' => true],
        'channelParticipants' => ['innerMadelineProto' => true],
        'session' => ['innerMadelineProto' => true, 'enableCache' => false],
    ];

    /**
     * Returns an instance of a client by session name.
     *
     * @internal
     */
    public static function giveInstanceBySession(string $session): MTProto
    {
        return self::$references[$session];
    }

    /**
     * Serialize session, returning object to serialize to db.
     *
     * @internal
     */
    public function serializeSession(object $data)
    {
        /** @psalm-suppress TypeDoesNotContainType */
        if (!isset($this->session) || $this->session instanceof MemoryArray) {
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
        if (self::$references) {
            Logger::log('Prompting final serialization (SHUTDOWN)...');
            foreach (self::$references as $instance) {
                if ($instance->authorized === API::LOGGED_OUT) {
                    continue;
                }
                $instance->wrapper->serialize();
            }
            Logger::log('Done final serialization (SHUTDOWN)!');
        }
    }

    private ?Future $initPromise = null;

    /**
     * Constructor function.
     *
     * @param Settings|SettingsEmpty $settings Settings
     * @param null|APIWrapper        $wrapper  API wrapper
     */
    public function __construct(Settings|SettingsEmpty $settings, ?APIWrapper $wrapper = null)
    {
        if ($wrapper) {
            $this->wrapper = $wrapper;
            self::$references[$this->getSessionName()] = $this;
        }

        $initDeferred = new DeferredFuture;
        $this->initPromise = $initDeferred->getFuture();
        try {
            $this->initialize($settings);
        } catch (Throwable $e) {
            try {
                $this->report((string) $e);
            } catch (Throwable) {
            }
            throw $e;
        } finally {
            $initDeferred->complete();
        }
    }

    /**
     * Initialization function.
     *
     * @internal
     */
    private function initialize(Settings|SettingsEmpty $settings): void
    {
        // Initialize needed stuffs
        Magic::start(light: false);
        // Parse and store settings
        $this->updateSettingsInternal($settings, false);
        // Start IPC server
        if (!$this->ipcServer) {
            $this->ipcServer = new Server($this);
            $this->ipcServer->setIpcPath($this->wrapper->getSession());
        }
        $this->ipcServer->start();
        // Actually instantiate needed classes like a boss
        $this->cleanupProperties();
        // Load rsa keys
        $this->rsa_keys = [];
        foreach ($this->settings->getConnection()->getRSAKeys() as $key) {
            $key = RSA::load($this->TL, $key);
            $this->rsa_keys[$key->fp] = $key;
        }
        $this->test_rsa_keys = [];
        foreach ($this->settings->getConnection()->getTestRSAKeys() as $key) {
            $key = RSA::load($this->TL, $key);
            $this->test_rsa_keys[$key->fp] = $key;
        }
        // (re)-initialize TL
        $callbacks = [$this, $this->peerDatabase];
        if ($this->settings->getDb()->getEnableFileReferenceDb()) {
            $callbacks []= $this->referenceDatabase;
        }
        if (!($this->authorization['user']['bot'] ?? false) && $this->settings->getDb()->getEnableMinDb()) {
            $callbacks[] = $this->minDatabase;
        }
        $this->TL->init($this->settings->getSchema(), $callbacks);
        $this->startLoops();
        $this->datacenter->currentDatacenter = $this->settings->getConnection()->getTestMode() ? 10002 : 2;
        $this->getConfig();
        $this->startUpdateSystem(true);
        $this->v = self::V;

        $this->settings->applyChanges();
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
     * Returns the session name.
     */
    public function getSessionName(): string
    {
        return $this->wrapper->getSession()->getSessionDirectoryPath();
    }

    private ?string $tmpDbPrefix = null;

    /** @internal */
    public function getDbPrefix(): string
    {
        $prefix = null;
        if ($this->settings->getDb() instanceof DriverDatabaseAbstract) {
            $prefix = $this->settings->getDb()->getEphemeralFilesystemPrefix();
        }
        $prefix ??= $this->getSelf()['id'] ?? null;
        if ($prefix === null) {
            $this->tmpDbPrefix ??= 'tmp_'.hash('xxh3', $this->getSessionName());
            $prefix = $this->tmpDbPrefix;
        }
        return (string) $prefix;
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
            'referenceDatabase',
            'minDatabase',
            'peerDatabase',
            'channelParticipants',
            'sponsoredMessages',

            'tmpDbPrefix',

            // Misc caching
            'searchingRightPts',
            'bottomPts',
            'topPts',
            'botDialogsUpdatesState',
            'cachedAllBotUsers',
            'dialog_params',
            'last_stored',
            'qres',
            'supportUser',
            'broadcasts',
            'broadcastId',
            'loginQrCode',
            'fetchedFullDialogs',

            // Event handler
            'event_handler',
            'event_handler_instance',
            'pluginInstances',
            'updates',
            'updates_key',
            'webhookUrl',

            'updateHandlerType',

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
            'secretChats',
            'temp_requested_secret_chats',

            // Report URI
            'reportDest',

            'calls',
            'callsByPeer',
            'snitch',
        ];
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
        if (empty($file)) {
            $file = basename(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'], '.php');
        }
        ($this->logger ?? Logger::$default)->logger($param, $level, $file);
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
    public function getTL(): TLInterface
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
     * Provide a stream for a file, URL or amp stream.
     */
    public function getStream(Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $stream, ?Cancellation $cancellation = null): ReadableStream
    {
        if ($stream instanceof LocalFile) {
            return openFile($stream->file, 'r');
        }
        if ($stream instanceof RemoteUrl) {
            $request = new Request($stream->url);
            $request->setTransferTimeout(INF);
            return $this->getHTTPClient()->request(
                $request,
                $cancellation
            )->getBody();
        }
        if ($stream instanceof Message) {
            $stream = $stream->media;
            if ($stream === null) {
                throw new AssertionError("The message must be a media message!");
            }
        }
        if ($stream instanceof Media) {
            return $stream->getStream(cancellation: $cancellation);
        }
        if ($stream instanceof BotApiFileId) {
            return $this->downloadToReturnedStream($stream, cancellation: $cancellation);
        }
        return $stream;
    }

    /**
     * Get async DNS client.
     */
    public function getDNSClient(): DnsResolver
    {
        return $this->datacenter->getDNSClient();
    }
    /**
     * Get contents of remote or local file asynchronously.
     *
     * @param string $filename Filename
     */
    public function fileGetContents(string $filename): string
    {
        if (filter_var($filename, FILTER_VALIDATE_URL)) {
            return $this->getHTTPClient()->request(new Request($filename))->getBody()->buffer();
        }
        return read($filename);
    }
    /**
     * Get main DC ID.
     *
     * @internal
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
        $this->serializeLoop ??= new PeriodicLoopInternal($this, $this->serialize(...), 'serialize', $this->settings->getSerialization()->getInterval());
        $this->phoneConfigLoop ??= new PeriodicLoopInternal($this, $this->getPhoneConfig(...), 'phone config', 3600);
        $this->configLoop ??= new PeriodicLoopInternal($this, $this->getConfig(...), 'config', 3600);

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
        if ($this->serializeLoop) {
            $this->serializeLoop->stop();
            $this->serializeLoop = null;
        }
        if ($this->phoneConfigLoop) {
            $this->phoneConfigLoop->stop();
            $this->phoneConfigLoop = null;
        }
        if ($this->configLoop) {
            $this->configLoop->stop();
            $this->configLoop = null;
        }
        if ($this->ipcServer) {
            $this->ipcServer->stop();
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
        $this->acceptChatMutex ??= new LocalKeyedMutex;
        $this->confirmChatMutex ??= new LocalKeyedMutex;
        $this->channels_state ??= new CombinedUpdatesState;
        $this->datacenter ??= new DataCenter($this);
        $this->snitch ??= new Snitch;

        $this->datacenter->__construct($this);

        $this->referenceDatabase ??= new ReferenceDatabase($this);
        $this->minDatabase ??= new MinDatabase($this);
        $this->peerDatabase ??= new PeerDatabase($this);

        $db = [];
        $db []= async($this->referenceDatabase->init(...));
        $db []= async($this->minDatabase->init(...));
        $db []= async($this->peerDatabase->init(...));
        $db []= async($this->initDb(...), $this);
        foreach ($this->secretChats as $chat) {
            $db []= async($chat->init(...));
        }
        await($db);

        if (isset($this->chats) && $this->chats instanceof MemoryArray) {
            $this->peerDatabase->importLegacy(
                $this->chats,
                $this->full_chats,
            );
        }

        if (!isset($this->TL)) {
            $this->TL = new TL($this);
            $callbacks = [$this, $this->referenceDatabase, $this->peerDatabase];
            if (!($this->authorization['user']['bot'] ?? false)) {
                $callbacks[] = $this->minDatabase;
            }
            $this->TL->init($this->settings->getSchema(), $callbacks);
        }

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
    }

    /**
     * Upgrade MadelineProto instance.
     */
    private function upgradeMadelineProto(): void
    {
        $this->logger->logger(Lang::$current_lang['serialization_ofd'], Logger::WARNING);
        foreach ($this->datacenter->getDataCenterConnections() as $dc_id => $socket) {
            if ($this->authorized === API::LOGGED_IN && \is_int($dc_id) && $socket->hasPermAuthKey() && $socket->hasTempAuthKey()) {
                $socket->bind();
                $socket->authorized(true);
            }
        }
        $this->settings->setSchema(new TLSchema);

        $this->resetMTProtoSession(true, true);
        $this->config = ['expires' => -1];
        $this->dh_config = ['version' => 0];
        $this->initialize($this->settings);
        foreach ($this->secretChats as $chat) {
            try {
                $chat->notifyLayer();
            } catch (RPCErrorException $e) {
            }
        }
    }
    /**
     * Post-deserialization initialization function.
     *
     * @param Settings|SettingsEmpty $settings New settings
     * @param APIWrapper             $wrapper  API wrapper
     *
     * @psalm-suppress UnsupportedPropertyReferenceUsage
     *
     * @internal
     */
    public function wakeup(SettingsAbstract $settings, APIWrapper $wrapper): void
    {
        // Setup one-time stuffs
        Magic::start(light: false);

        // Set API wrapper
        $this->wrapper = $wrapper;
        // Set reference to itself
        self::$references[$this->getSessionName()] = $this;

        $deferred = new DeferredFuture;
        $this->initPromise = $deferred->getFuture();

        try {
            // Setup logger
            $this->setupLogger();
            if (!$this->ipcServer) {
                $this->ipcServer = new Server($this);
                $this->ipcServer->setIpcPath($this->wrapper->getSession());
            }
            $this->ipcServer->start();

            // Cleanup old properties, init new stuffs
            $this->cleanupProperties();

            // Re-set TL closures
            $callbacks = [$this, $this->peerDatabase];
            if ($this->settings->getDb()->getEnableFileReferenceDb()) {
                $callbacks []= $this->referenceDatabase;
            }
            if (!($this->authorization['user']['bot'] ?? false) && $this->settings->getDb()->getEnableMinDb()) {
                $callbacks[] = $this->minDatabase;
            }

            $this->TL->updateCallbacks($callbacks);

            $this->settings->getConnection()->init();
            // Setup language
            Lang::$current_lang =& Lang::$lang['en'];
            if (Lang::$lang[$this->settings->getAppInfo()->getLangCode()] ?? false) {
                Lang::$current_lang =& Lang::$lang[$this->settings->getAppInfo()->getLangCode()];
            }
            // Reset MTProto session (not related to user session)
            $this->resetMTProtoSession();
            // Update settings from constructor
            $this->updateSettings($settings);
            // Update TL callbacks
            $callbacks = [$this, $this->peerDatabase];
            if ($this->settings->getDb()->getEnableFileReferenceDb()) {
                $callbacks[] = $this->referenceDatabase;
            }
            if ($this->settings->getDb()->getEnableMinDb() && !($this->authorization['user']['bot'] ?? false)) {
                $callbacks[] = $this->minDatabase;
            }
            // Connect to all DCs, start internal loops
            if ($this->fullGetSelf()) {
                $this->authorized = API::LOGGED_IN;
                $this->setupLogger();
                $this->startLoops();
                $this->getCdnConfig();
            } else {
                $this->startLoops();
            }
            // onStart event handler
            if ($this->event_handler && class_exists($this->event_handler) && is_subclass_of($this->event_handler, EventHandler::class)) {
                $this->setEventHandler($this->event_handler);
            }
            $this->startUpdateSystem(true);
            $this->cacheFullDialogs();
            if ($this->authorized === API::LOGGED_IN) {
                $this->logger->logger("Obtaining updates after deserialization...", Logger::NOTICE);
                $this->updaters[UpdateLoop::GENERIC]->resume();
            }
            $this->updaters[UpdateLoop::GENERIC]->start();

            foreach ($this->broadcasts as $broadcast) {
                $broadcast->resume();
            }

            foreach ($this->calls as $id => $call) {
                if ($call->getCallState() === CallState::ENDED) {
                    $this->cleanupCall($id);
                } elseif ($call->getCallState() === CallState::REQUESTED && time() - $call->public->date > 5*60) {
                    EventLoop::queue($call->discard(...));
                }
            }
        } catch (Throwable $e) {
            try {
                $this->report((string) $e);
            } catch (Throwable) {
            }
            throw $e;
        } finally {
            $deferred->complete();
        }
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
        if (isset($this->wrapper) && isset(self::$references[$this->getSessionName()])) {
            unset(self::$references[$this->getSessionName()]);
        }
        $this->stopLoops();
        if (isset($this->seqUpdater)) {
            $this->seqUpdater->stop();
        }
        if (isset($this->channels_state)) {
            $channelIds = [];
            foreach ($this->channels_state->get() as $state) {
                $channelIds[] = $state->getChannel();
            }
            sort($channelIds);
            foreach ($channelIds as $channelId) {
                if (isset($this->feeders[$channelId])) {
                    $this->feeders[$channelId]->stop();
                }
                if (isset($this->updaters[$channelId])) {
                    $this->updaters[$channelId]->stop();
                }
            }
        }
        if (isset($this->datacenter)) {
            foreach ($this->datacenter->getDataCenterConnections() as $datacenter) {
                $datacenter->disconnect();
            }
        }
        $this->logger->logger('Unreferenced instance');
        if ($this->authorized === API::LOGGED_OUT) {
            $this->wrapper->getSession()->delete();
        }
    }
    /** @internal */
    public function isCdn(int $dc): bool
    {
        $test = $this->settings->getConnection()->getTestMode() ? 'test' : 'main';
        $ipv6 = $this->settings->getConnection()->getIpv6() ? 'ipv6' : 'ipv4';
        return $this->dcList[$test][$ipv6][$dc]['cdn'] ?? false;
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
                if ($this->v !== self::V || $this->settings->getSchema()->needsUpgrade()) {
                    $this->logger->logger("Generic settings have changed!", Logger::WARNING);
                    $this->upgradeMadelineProto();
                }
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
        if ($this->settings->getSerialization()->hasChanged()) {
            $this->logger->logger("The serialization settings have changed!", Logger::WARNING);
            if (isset($this->serializeLoop)) {
                $this->serializeLoop->stop();
            }
            $this->serializeLoop = new PeriodicLoopInternal($this, $this->serialize(...), 'serialize', $this->settings->getSerialization()->applyChanges()->getInterval());
            $this->serializeLoop->start();
        }
        if ($recurse && ($this->settings->getAuth()->hasChanged()
            || $this->settings->getConnection()->hasChanged()
            || $this->settings->getSchema()->hasChanged()
            || $this->settings->getSchema()->needsUpgrade()
            || $this->v !== self::V)) {
            $this->logger->logger("Generic settings have changed!", Logger::WARNING);
            if ($this->v !== self::V || $this->settings->getSchema()->needsUpgrade()) {
                $this->upgradeMadelineProto();
            }
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
     *
     * @internal
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
        foreach ($this->datacenter->getDataCenterConnections() as $id => $socket) {
            if ($de) {
                $socket->resetSession();
            }
            if ($auth_key) {
                $socket->setTempAuthKey(null);
            }
        }
    }
    /**
     * Reset the update state and fetch all updates from the beginning.
     */
    public function resetUpdateState(): void
    {
        if (isset($this->seqUpdater)) {
            $this->seqUpdater->stop();
        }
        $channelIds = [];
        $newStates = [];
        foreach ($this->channels_state->get() as $state) {
            $channelIds[] = $state->getChannel();
            $channelId = $state->getChannel();
            $pts = $state->pts();
            $pts = $channelId ? max(1, $pts - 1000000) : ($pts > 4000000 ? $pts - 1000000 : max(1, $pts - 1000000));
            $newStates[$channelId] = new UpdatesState(['pts' => $pts], $channelId);
        }
        sort($channelIds);
        foreach ($channelIds as $channelId) {
            if (isset($this->feeders[$channelId])) {
                $this->feeders[$channelId]->stop();
            }
            if (isset($this->updaters[$channelId])) {
                $this->updaters[$channelId]->stop();
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
        $this->channels_state->get(FeedLoop::GENERIC);
        $channelIds = [];
        foreach ($this->channels_state->get() as $state) {
            $channelIds[] = $state->getChannel();
        }
        sort($channelIds);
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
        $this->seqUpdater->start();
        $this->seqUpdater->resume();
        foreach ($this->secretChats as $chat) {
            $chat->startFeedLoop();
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
        if ($this->authorized === API::LOGGED_IN
            && class_exists(VoIPServerConfigInternal::class)
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
            foreach (($this->methodCallAsyncRead('help.getCdnConfig', [], $this->authorized_dc))['public_keys'] as $curkey) {
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
     * @param array $config Current config
     */
    public function getConfig(array $config = []): array
    {
        if ($this->config['expires'] > time()) {
            return $this->config;
        }
        $this->config = empty($config) ? $this->methodCallAsyncRead('help.getConfig', $config) : $config;
        $this->parseConfig();
        $this->logger->logger('Updated config!', Logger::NOTICE);
        return $this->config;
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
     * Whether we're currently connected to the test DCs.
     *
     * @return boolean
     */
    public function isTestMode(): bool
    {
        return $this->settings->getConnection()->getTestMode();
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
        $this->dcList = $new;
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
     * Returns whether the current user is a bot.
     */
    public function isSelfBot(): bool
    {
        return $this->authorization['user']['bot'];
    }
    /**
     * Returns whether the current user is a user.
     */
    public function isSelfUser(): bool
    {
        return !$this->authorization['user']['bot'];
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
        return $this->getSelf();
    }
    /**
     * Get authorization info.
     *
     * @return \danog\MadelineProto\API::NOT_LOGGED_IN|\danog\MadelineProto\API::WAITING_CODE|\danog\MadelineProto\API::WAITING_SIGNUP|\danog\MadelineProto\API::WAITING_PASSWORD|\danog\MadelineProto\API::LOGGED_IN|API::LOGGED_OUT
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
        if ($this->authorized !== API::WAITING_PASSWORD) {
            throw new Exception('Not waiting for the password!');
        }
        Assert::string($this->authorization['hint']);
        return $this->authorization['hint'];
    }
    /**
     * IDs of peers where to report errors.
     *
     * @var list<int>
     */
    private array $reportDest = [];
    /**
     * Admin IDs.
     *
     * @var list<int>
     */
    private array $admins = [];
    /**
     * Check if has report peers.
     */
    public function hasReportPeers(): bool
    {
        return (bool) $this->reportDest;
    }
    /**
     * Check if has admins.
     */
    public function hasAdmins(): bool
    {
        return (bool) $this->admins;
    }
    /**
     * Get admin IDs (equal to all user report peers).
     */
    public function getAdminIds(): array
    {
        return $this->admins;
    }
    /**
     * Get a message to show to the user when starting the bot.
     */
    public function getWebMessage(string $message): string
    {
        Logger::log($message);

        $warning = $this->getWebWarnings();
        if ($this->hasEventHandler()) {
            if (!$this->hasReportPeers()) {
                Logger::log('!!! '.Lang::$current_lang['noReportPeers'].' !!!', Logger::FATAL_ERROR);
                Logger::log("!!! public function getReportPeers() { return '@yourtelegramusername'; } !!!", Logger::FATAL_ERROR);
                $warning .= "<h2 style='color:red;'>".htmlentities(Lang::$current_lang['noReportPeers'])."</h2>";
                $warning .= "<code>public function getReportPeers() { return '@yourtelegramusername'; }</code>";
            }
            if ($this->event_handler_instance instanceof EventHandler) {
                $issues = Tools::validateEventHandlerClass($this->event_handler_instance::class);
                foreach ($issues as $issue) {
                    $issue->log();
                    $warning .= $issue->getHTML();
                }
            }
            foreach ($this->pluginInstances as $class => $_) {
                $issues = Tools::validateEventHandlerClass($class);
                foreach ($issues as $issue) {
                    $issue->log();
                    $warning .= $issue->getHTML();
                }
            }
        }

        return "<html><body><h1>$message</h1>$warning</body></html>";
    }
    /**
     * Get various warnings to show to the user in the web UI.
     */
    public static function getWebWarnings(): string
    {
        Magic::start(light: false);
        $warning = '';
        if (API::RELEASE !== Magic::$latest_release) {
            $warning .= "<h2 style='color:red;'>".htmlentities(Lang::$current_lang['update_madelineproto']).'</h2>';
        }
        if (!Magic::$hasOpenssl) {
            $warning .= "<h2 style='color:red;'>".htmlentities(sprintf(Lang::$current_lang['extensionRecommended'], 'openssl'))."</h2>";
        }
        if (!\extension_loaded('gmp')) {
            $warning .= "<h2 style='color:red;'>".htmlentities(sprintf(Lang::$current_lang['extensionRecommended'], 'gmp'))."</h2>";
        }
        if (!\extension_loaded('uv')) {
            $warning .= "<p>".htmlentities(sprintf(Lang::$current_lang['extensionRecommended'], 'uv'))."</p>";
        }
        return $warning;
    }

    /**
     * Sanitize peer(s) where to send errors occurred in the event loop.
     *
     * @internal
     * @param int|string|array<int|string> $userOrId Username(s) or peer ID(s)
     *
     * @return array<int>
     */
    public function sanitizeReportPeers(int|string|array $userOrId): array
    {
        if (!(\is_array($userOrId) && !isset($userOrId['_']) && !isset($userOrId['id']))) {
            $userOrId = [$userOrId];
        }
        $selfBot = $this->getSelf()['bot'];
        foreach ($userOrId as $k => &$peer) {
            try {
                $peer = $this->getInfo($peer);
                $type = $peer['type'];
                $peer = $peer['bot_api_id'];
                if ($type === 'bot' && $selfBot) {
                    unset($userOrId[$k]);
                    $this->logger("Can't use a bot as report peer: $peer", Logger::FATAL_ERROR);
                    continue;
                }
            } catch (Throwable $e) {
                unset($userOrId[$k]);
                $peer = json_encode($peer);
                $this->logger("Could not obtain info about report peer $peer: $e", Logger::FATAL_ERROR);
            }
        }
        /** @var array<int> $userOrId */
        return array_values($userOrId);
    }
    /**
     * Set peer(s) where to send errors occurred in the event loop.
     *
     * @param int|string|array<int|string> $userOrId Username(s) or peer ID(s)
     */
    public function setReportPeers(int|string|array $userOrId): void
    {
        $this->reportDest = $this->sanitizeReportPeers($userOrId);
        $this->admins = array_values(array_filter($this->reportDest, static fn (int $v) => $v > 0));
    }
    /**
     * Flush all postponed messages.
     */
    public function flush(): void
    {
        $this->waitForInit();
        foreach ($this->datacenter->getDataCenterConnections() as $conn) {
            $conn->flush();
        }
    }
    private ?LocalMutex $reportMutex = null;
    /**
     * Sends a message to all report peers (admins of the bot).
     *
     * @param string       $message      Message to send
     * @param ParseMode    $parseMode    Parse mode
     * @param array|null   $replyMarkup  Keyboard information.
     * @param integer|null $scheduleDate Schedule date.
     * @param boolean      $silent       Whether to send the message silently, without triggering notifications.
     * @param boolean      $background   Send this message as background message
     * @param boolean      $clearDraft   Clears the draft field
     * @param boolean      $noWebpage    Set this flag to disable generation of the webpage preview
     *
     * @return list<\danog\MadelineProto\EventHandler\Message>
     */
    public function sendMessageToAdmins(
        string $message,
        ParseMode $parseMode = ParseMode::TEXT,
        ?array $replyMarkup = null,
        ?int $scheduleDate = null,
        bool $silent = false,
        bool $noForwards = false,
        bool $background = false,
        bool $clearDraft = false,
        bool $noWebpage = false,
        ?Cancellation $cancellation = null
    ): array {
        $result = [];
        foreach ($this->admins as $report) {
            $result []= $this->sendMessage(
                peer: $report,
                message: $message,
                parseMode: $parseMode,
                replyMarkup: $replyMarkup,
                scheduleDate: $scheduleDate,
                silent: $silent,
                noForwards: $noForwards,
                background: $background,
                clearDraft: $clearDraft,
                noWebpage: $noWebpage,
                cancellation: $cancellation
            );
        }
        return $result;
    }
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

        $this->reportCache ??= new LocalCache();
        if ($this->reportCache->get($message)) {
            return;
        }
        $this->reportCache->set($message, true, 60);

        $this->reportMutex ??= new LocalMutex;
        $lock = $this->reportMutex->acquire();
        try {
            $file = null;
            if ($this->settings->getLogger()->getType() === Logger::FILE_LOGGER
                && $path = $this->settings->getLogger()->getExtra()) {
                $temp = tempnam(sys_get_temp_dir(), 'madelinelog');
                copy($path, $temp);
                $path = $temp;
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
                    deleteFile($path);
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
            EventLoop::queue($lock->release(...));
        }
    }
    /**
     * Report memory profile with memprof.
     */
    public function reportMemoryProfile(): void
    {
        if (!\extension_loaded('memprof')) {
            throw Exception::extension('memprof');
        }
        if (!memprof_enabled()) {
            throw new Exception("Memory profiling is not enabled, set the MEMPROF_PROFILE=1 environment variable or GET parameter to enable it.");
        }

        $current = "Current memory usage: ".round(memory_get_usage()/1024/1024, 1) . " MB";
        $file = fopen('php://memory', 'r+');
        memprof_dump_pprof($file);
        fseek($file, 0);
        $file = [
            '_' => 'inputMediaUploadedDocument',
            'file' => $file,
            'attributes' => [
                ['_' => 'documentAttributeFilename', 'file_name' => 'report.pprof'],
            ],
        ];
        foreach ($this->reportDest as $id) {
            try {
                $this->methodCallAsyncRead('messages.sendMedia', ['peer' => $id, 'message' => $current, 'media' => $file]);
            } catch (Throwable $e) {
                $this->logger("While reporting memory profile to $id: $e", Logger::FATAL_ERROR);
            }
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
        return array_merge($methods, get_class_methods(InternalDoc::class));
    }
    /**
     * @internal
     */
    public function getMethodAfterResponseDeserializationCallbacks(): array
    {
        return [];
    }
    /**
     * @internal
     */
    public function getMethodBeforeResponseDeserializationCallbacks(): array
    {
        return [];
    }
    /**
     * @internal
     */
    public function getConstructorAfterDeserializationCallbacks(): array
    {
        return [
            'help.support' => [function (array $support): void {
                $this->supportUser = $support['user']['id'];
            }],
            'config' => [function (array $config): void {
                $this->config = $config;
            }],
        ];
    }
    /**
     * @internal
     */
    public function getConstructorBeforeDeserializationCallbacks(): array
    {
        return [];
    }
    /**
     * @internal
     */
    public function getConstructorBeforeSerializationCallbacks(): array
    {
        return [];
    }
    /**
     * @internal
     */
    public function getTypeMismatchCallbacks(): array
    {
        return array_merge(
            array_fill_keys(
                [
                    'InputUser',
                    'InputChannel',
                ],
                $this->getInputConstructor(...),
            ),
            array_fill_keys(
                [
                    'User',
                    'Chat',
                    'Peer',
                    'InputDialogPeer',
                    'InputNotifyPeer',
                ],
                $this->getInfo(...),
            ),
            array_fill_keys(
                [
                    'InputMedia',
                    'InputDocument',
                    'InputPhoto',
                ],
                $this->getFileInfo(...),
            ),
            [
                'InputFileLocation' => $this->getDownloadInfo(...),
                'InputPeer' => $this->getInputPeer(...),
                'InputCheckPasswordSRP' => fn (string $password): array => (new PasswordCalculator($this->methodCallAsyncRead('account.getPassword', [])))->getCheckPassword($password),
            ],
        );
    }
    /**
     * Get debug information for var_dump.
     */
    public function __debugInfo(): array
    {
        $vars = get_object_vars($this);
        unset($vars['peerDatabase'], $vars['referenceDatabase'], $vars['minDatabase'], $vars['TL']);
        return $vars;
    }
}
