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
use danog\MadelineProto\Broadcast\Broadcast;
use danog\MadelineProto\Db\DbArray;
use danog\MadelineProto\Db\DbPropertiesFactory;
use danog\MadelineProto\Db\DbPropertiesTrait;
use danog\MadelineProto\Db\MemoryArray;
use danog\MadelineProto\EventHandler\Message;
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
use danog\MadelineProto\MTProtoTools\PasswordCalculator;
use danog\MadelineProto\MTProtoTools\PeerHandler;
use danog\MadelineProto\MTProtoTools\ReferenceDatabase;
use danog\MadelineProto\MTProtoTools\UpdateHandler;
use danog\MadelineProto\MTProtoTools\UpdatesState;
use danog\MadelineProto\SecretChats\MessageHandler;
use danog\MadelineProto\SecretChats\ResponseHandler;
use danog\MadelineProto\SecretChats\SeqNoHandler;
use danog\MadelineProto\Settings\Database\SerializerType;
use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\TL\Conversion\BotAPI;
use danog\MadelineProto\TL\Conversion\BotAPIFiles;
use danog\MadelineProto\TL\Conversion\TD;
use danog\MadelineProto\TL\TL;
use danog\MadelineProto\TL\TLCallback;
use danog\MadelineProto\TL\TLInterface;
use danog\MadelineProto\TL\Types\LoginQrCode;
use danog\MadelineProto\Wrappers\Ads;
use danog\MadelineProto\Wrappers\Button;
use danog\MadelineProto\Wrappers\DialogHandler;
use danog\MadelineProto\Wrappers\Events;
use danog\MadelineProto\Wrappers\Login;
use danog\MadelineProto\Wrappers\Loop;
use danog\MadelineProto\Wrappers\Start;
use Psr\Log\LoggerInterface;
use Throwable;
use Webmozart\Assert\Assert;

use function Amp\async;
use function Amp\File\deleteFile;
use function Amp\File\getSize;
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
    const V = 170;
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
     * Phone config loop.
     */
    public ?PeriodicLoopInternal $phoneConfigLoop = null;
    /**
     * Config loop.
     */
    public ?PeriodicLoopInternal $configLoop = null;
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
    private ?LoginQrCode $loginQrCode = null;
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
        'chats' => ['innerMadelineProto' => true],
        'full_chats' => ['innerMadelineProto' => true],
        'sponsoredMessages' => ['innerMadelineProto' => true],
        'channelParticipants' => ['innerMadelineProto' => true],
        'usernames' => ['innerMadelineProto' => true, 'innerMadelineProtoSerializer' => SerializerType::STRING],
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
     * @param null|APIWrapper            $wrapper  API wrapper
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
        // Actually instantiate needed classes like a boss
        $this->cleanupProperties();
        // Start IPC server
        if (!$this->ipcServer) {
            try {
                $this->ipcServer = new Server($this);
                $this->ipcServer->setSettings($this->settings->getIpc());
                $this->ipcServer->setIpcPath($this->wrapper->getSession());
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
        $this->datacenter->currentDatacenter = $this->config['test_mode'] ? 10002 : 2;
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
            'secret_chats',
            'temp_requested_secret_chats',
            'temp_rekeyed_secret_chats',

            // Report URI
            'reportDest',

            'calls',
            'snitch',
        ];
    }

    private bool $runningUsernameMigration = false;
    private function fillUsernamesCache(): void
    {
        if (!$this->settings->getDb()->getEnableUsernameDb()) {
            $this->usernames->clear();
            return;
        }
        if (!isset($this->usernames[' '])) {
            if ($this->runningUsernameMigration) {
                return;
            }
            $this->runningUsernameMigration = true;
            $this->logger('Filling database cache. This can take a few minutes.', Logger::WARNING);

            async(function (): void {
                try {
                    $counter = 0;
                    foreach ($this->chats as $id => $chat) {
                        $counter++;
                        $id = (int) $id;
                        if (isset($chat['username'])) {
                            $this->usernames[\strtolower($chat['username'])] = $id;
                        }
                        foreach ($chat['usernames'] ?? [] as ['username' => $username]) {
                            $this->usernames[\strtolower($username)] = $id;
                        }
                        if ($counter % 1000 === 0) {
                            $this->logger("Filling database cache. $counter", Logger::WARNING);
                        }
                    }
                    $this->usernames[' '] = 0;
                    $this->logger('Cache filled.', Logger::WARNING);
                } finally {
                    $this->runningUsernameMigration = false;
                }
            });
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
     * @internal
     * @return array<DataCenterConnection>
     */
    public function getDataCenterConnections(): array
    {
        return $this->datacenter->getDataCenterConnections();
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
        $this->callCheckerLoop ??= new PeriodicLoopInternal($this, $this->checkCalls(...), 'call check', 10);
        $this->serializeLoop ??= new PeriodicLoopInternal($this, $this->serialize(...), 'serialize', $this->settings->getSerialization()->getInterval());
        $this->phoneConfigLoop ??= new PeriodicLoopInternal($this, $this->getPhoneConfig(...), 'phone config', 3600);
        $this->configLoop ??= new PeriodicLoopInternal($this, $this->getConfig(...), 'config', 3600);

        if (!$this->ipcServer) {
            try {
                $this->ipcServer = new Server($this);
                $this->ipcServer->setSettings($this->settings->getIpc());
                $this->ipcServer->setIpcPath($this->wrapper->getSession());
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
            $this->callCheckerLoop->stop();
            $this->callCheckerLoop = null;
        }
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
        $this->logger->logger(Lang::$current_lang['serialization_ofd'], Logger::WARNING);
        foreach ($this->datacenter->getDataCenterConnections() as $dc_id => $socket) {
            if ($this->authorized === API::LOGGED_IN && \is_int($dc_id) && $socket->hasPermAuthKey() && $socket->hasTempAuthKey()) {
                $socket->bind();
                $socket->authorized(true);
            }
        }
        $this->settings->setSchema(new TLSchema);
        if (!isset($this->usernames[' '])) {
            $this->usernames->clear();
        }

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

        $this->minDatabase?->sync();
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
                $this->authorized = API::LOGGED_IN;
                $this->setupLogger();
                $this->startLoops();
                $this->getCdnConfig();
                $this->initAuthorization();
            } else {
                $this->startLoops();
            }
            // onStart event handler
            if ($this->event_handler && \class_exists($this->event_handler) && \is_subclass_of($this->event_handler, EventHandler::class)) {
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
        if (isset(self::$references[$this->getSessionName()])) {
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
            \sort($channelIds);
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
                $datacenter->setExtra($this);
                $datacenter->disconnect();
            }
        }
        $this->logger->logger('Unreferenced instance');
        if ($this->authorized === API::LOGGED_OUT) {
            $this->wrapper->getSession()->delete();
        }
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
        if ($this->settings->getIpc()->hasChanged()) {
            $this->logger->logger("The IPC settings have changed!", Logger::WARNING);
            if (isset($this->ipcServer)) {
                $this->ipcServer->setSettings($this->settings->getIpc()->applyChanges());
            }
        }
        if ($this->settings->getSerialization()->hasChanged()) {
            $this->logger->logger("The serialization settings have changed!", Logger::WARNING);
            if (isset($this->serializeLoop)) {
                $this->serializeLoop->stop();
            }
            $this->serializeLoop = new PeriodicLoopInternal($this, [$this, 'serialize'], 'serialize', $this->settings->getSerialization()->applyChanges()->getInterval());
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
     * Checks whether all datacenters are authorized.
     *
     * @internal
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
     * @internal
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
            $this->seqUpdater->stop();
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
        if ($this->authorized === API::LOGGED_IN
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
        $this->logger->logger('Updated config!', Logger::NOTICE);
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
     * Whether we're currently connected to the test DCs.
     *
     * @return boolean
     */
    public function isTestMode(): bool
    {
        return $this->config['test_mode'];
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
                $warning .= "<h2 style='color:red;'>".\htmlentities(Lang::$current_lang['noReportPeers'])."</h2>";
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
            $warning .= "<h2 style='color:red;'>".\htmlentities(Lang::$current_lang['update_madelineproto']).'</h2>';
        }
        if (!Magic::$hasOpenssl) {
            $warning .= "<h2 style='color:red;'>".\htmlentities(\sprintf(Lang::$current_lang['extensionRecommended'], 'openssl'))."</h2>";
        }
        if (!\extension_loaded('gmp')) {
            $warning .= "<h2 style='color:red;'>".\htmlentities(\sprintf(Lang::$current_lang['extensionRecommended'], 'gmp'))."</h2>";
        }
        if (!\extension_loaded('uv')) {
            $warning .= "<h2 style='color:red;'>".\htmlentities(\sprintf(Lang::$current_lang['extensionRecommended'], 'uv'))."</h2>";
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
                $peer = \json_encode($peer);
                $this->logger("Could not obtain info about report peer $peer: $e", Logger::FATAL_ERROR);
            }
        }
        /** @var array<int> $userOrId */
        return \array_values($userOrId);
    }
    /**
     * Set peer(s) where to send errors occurred in the event loop.
     *
     * @param int|string|array<int|string> $userOrId Username(s) or peer ID(s)
     */
    public function setReportPeers(int|string|array $userOrId): void
    {
        $this->reportDest = $this->sanitizeReportPeers($userOrId);
        $this->admins = \array_values(\array_filter($this->reportDest, fn (int $v) => $v > 0));
    }
    private ?LocalMutex $reportMutex = null;
    /**
     * Sends a message to all report peers (admins of the bot).
     *
     * @param string $message Message to send
     * @param ParseMode $parseMode Parse mode
     * @param array|null $replyMarkup Keyboard information.
     * @param integer|null $scheduleDate Schedule date.
     * @param boolean $silent Whether to send the message silently, without triggering notifications.
     * @param boolean $background Send this message as background message
     * @param boolean $clearDraft Clears the draft field
     * @param boolean $noWebpage Set this flag to disable generation of the webpage preview
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
                noWebpage: $noWebpage
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
        $this->reportMutex ??= new LocalMutex;
        $lock = $this->reportMutex->acquire();
        try {
            $file = null;
            if ($this->settings->getLogger()->getType() === Logger::FILE_LOGGER
                && $path = $this->settings->getLogger()->getExtra()) {
                $temp = \tempnam(\sys_get_temp_dir(), 'madelinelog');
                \copy($path, $temp);
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
            $lock->release();
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
        if (!\memprof_enabled()) {
            throw new Exception("Memory profiling is not enabled, set the MEMPROF_PROFILE=1 environment variable or GET parameter to enable it.");
        }

        $current = "Current memory usage: ".\round(\memory_get_usage()/1024/1024, 1) . " MB";
        $file = \fopen('php://memory', 'r+');
        \memprof_dump_pprof($file);
        \fseek($file, 0);
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
        return \array_merge($methods, \get_class_methods(InternalDoc::class));
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
        return \array_merge(
            \array_fill_keys(['chat', 'chatEmpty', 'chatForbidden', 'channel', 'channelEmpty', 'channelForbidden'], [$this->addChat(...)]),
            \array_fill_keys(['user', 'userEmpty'], [$this->addUser(...)]),
            \array_fill_keys(['chatFull', 'channelFull', 'userFull'], [$this->addFullChat(...)]),
            ['help.support' => [$this->addSupport(...)]],
            ['config' => [$this->addConfig(...)]],
        );
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
        return \array_merge(
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
            [
                'InputFileLocation' => $this->getDownloadInfo(...),
                'InputPeer' => $this->getInputPeer(...),
                'InputCheckPasswordSRP' => function (string $password): array {
                    return (new PasswordCalculator($this->methodCallAsyncRead('account.getPassword', [])))->getCheckPassword($password);
                },
            ],
        );
    }
    /**
     * @internal
     */
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
