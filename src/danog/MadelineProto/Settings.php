<?php

namespace danog\MadelineProto;

use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Settings\Auth;
use danog\MadelineProto\Settings\Connection;
use danog\MadelineProto\Settings\Database\Memory as DatabaseMemory;
use danog\MadelineProto\Settings\Database\Mysql;
use danog\MadelineProto\Settings\Database\Postgres;
use danog\MadelineProto\Settings\Database\Redis;
use danog\MadelineProto\Settings\DatabaseAbstract;
use danog\MadelineProto\Settings\Files;
use danog\MadelineProto\Settings\Ipc;
use danog\MadelineProto\Settings\Logger;
use danog\MadelineProto\Settings\Peer;
use danog\MadelineProto\Settings\Pwr;
use danog\MadelineProto\Settings\RPC;
use danog\MadelineProto\Settings\SecretChats;
use danog\MadelineProto\Settings\Serialization;
use danog\MadelineProto\Settings\Templates;
use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\Settings\VoIP;

/**
 * Settings class used for configuring MadelineProto.
 */
class Settings extends SettingsAbstract
{
    /**
     * App information.
     */
    protected AppInfo $appInfo;
    /**
     * Cryptography settings.
     */
    protected Auth $auth;
    /**
     * Connection settings.
     */
    protected Connection $connection;
    /**
     * File management settings.
     */
    protected Files $files;
    /**
     * IPC server settings.
     */
    protected Ipc $ipc;
    /**
     * Logger settings.
     */
    protected Logger $logger;
    /**
     * Peer database settings.
     */
    protected Peer $peer;
    /**
     * PWRTelegram settings.
     */
    protected Pwr $pwr;
    /**
     * RPC settings.
     */
    protected RPC $rpc;
    /**
     * Secret chat settings.
     */
    protected SecretChats $secretChats;
    /**
     * Serialization settings.
     */
    protected Serialization $serialization;
    /**
     * TL schema settings.
     */
    protected TLSchema $schema;
    /**
     * DatabaseAbstract settings.
     */
    protected DatabaseAbstract $db;
    /**
     * Template settings.
     */
    protected Templates $templates;
    /**
     * VoIP settings.
     */
    protected VoIP $voip;

    /**
     * Create settings object from possibly legacy settings array.
     *
     * @internal
     *
     * @param SettingsAbstract|array $settings Settings
     *
     * @return SettingsAbstract
     */
    public static function parseFromLegacy($settings): SettingsAbstract
    {
        if (\is_array($settings)) {
            if (empty($settings)) {
                return new SettingsEmpty;
            }
            $settingsNew = new Settings;
            $settingsNew->mergeArray($settings);
            return $settingsNew;
        }
        return $settings;
    }
    /**
     * Create full settings object from possibly legacy settings array.
     *
     * @internal
     *
     * @param SettingsAbstract|array $settings Settings
     *
     * @return Settings
     */
    public static function parseFromLegacyFull($settings): Settings
    {
        $settings = self::parseFromLegacy($settings);
        if (!$settings instanceof Settings) {
            $newSettings = new Settings;
            $newSettings->merge($settings);
            $settings = $newSettings;
        }
        return $settings;
    }
    /**
     * Constructor.
     *
     * @internal
     */
    public function __construct()
    {
        $this->appInfo = new AppInfo;
        $this->auth = new Auth;
        $this->connection = new Connection;
        $this->files = new Files;
        $this->logger = new Logger;
        $this->peer = new Peer;
        $this->pwr = new Pwr;
        $this->rpc = new RPC;
        $this->secretChats = new SecretChats;
        $this->serialization = new Serialization;
        $this->schema = new TLSchema;
        $this->db = new DatabaseMemory;
        $this->templates = new Templates;
        $this->ipc = new IPc;
        $this->voip = new VoIP;
    }
    public function __wakeup()
    {
        if (!isset($this->voip)) {
            $this->voip = new VoIP;
        }
    }
    /**
     * Merge legacy array settings.
     *
     * @param array $settings Settings
     *
     * @internal
     *
     * @return void
     */
    public function mergeArray(array $settings): void
    {
        $this->appInfo->mergeArray($settings);
        $this->auth->mergeArray($settings);
        $this->connection->mergeArray($settings);
        $this->files->mergeArray($settings);
        $this->logger->mergeArray($settings);
        $this->peer->mergeArray($settings);
        $this->pwr->mergeArray($settings);
        $this->rpc->mergeArray($settings);
        $this->secretChats->mergeArray($settings);
        $this->serialization->mergeArray($settings);
        $this->schema->mergeArray($settings);
        $this->ipc->mergeArray($settings);
        $this->voip->mergeArray($settings);

        switch ($settings['db']['type'] ?? 'memory') {
            case 'memory':
                $this->db = new DatabaseMemory;
                break;
            case 'mysql':
                $this->db = new Mysql;
                break;
            case 'postgres':
                $this->db = new Postgres;
                break;
            case 'redis':
                $this->db = new Redis;
                break;
        }
        $this->db->mergeArray($settings);
    }

    /**
     * Merge another instance of settings.
     *
     * @param SettingsAbstract $settings Settings
     *
     * @return void
     */
    public function merge(SettingsAbstract $settings): void
    {
        if (!$settings instanceof self) {
            if ($settings instanceof AppInfo) {
                $this->appInfo->merge($settings);
            } elseif ($settings instanceof Auth) {
                $this->auth->merge($settings);
            } elseif ($settings instanceof Connection) {
                $this->connection->merge($settings);
            } elseif ($settings instanceof Files) {
                $this->files->merge($settings);
            } elseif ($settings instanceof Logger) {
                $this->logger->merge($settings);
            } elseif ($settings instanceof Peer) {
                $this->peer->merge($settings);
            } elseif ($settings instanceof Pwr) {
                $this->pwr->merge($settings);
            } elseif ($settings instanceof RPC) {
                $this->rpc->merge($settings);
            } elseif ($settings instanceof SecretChats) {
                $this->secretChats->merge($settings);
            } elseif ($settings instanceof Serialization) {
                $this->serialization->merge($settings);
            } elseif ($settings instanceof TLSchema) {
                $this->schema->merge($settings);
            } elseif ($settings instanceof Ipc) {
                $this->ipc->merge($settings);
            } elseif ($settings instanceof Templates) {
                $this->templates->merge($settings);
            } elseif ($settings instanceof VoIP) {
                $this->voip->merge($settings);
            } elseif ($settings instanceof DatabaseAbstract) {
                if (!$this->db instanceof $settings) {
                    $this->db = $settings;
                } else {
                    $this->db->merge($settings);
                }
            }
            return;
        }
        $this->appInfo->merge($settings->appInfo);
        $this->auth->merge($settings->auth);
        $this->connection->merge($settings->connection);
        $this->files->merge($settings->files);
        $this->logger->merge($settings->logger);
        $this->peer->merge($settings->peer);
        $this->pwr->merge($settings->pwr);
        $this->rpc->merge($settings->rpc);
        $this->secretChats->merge($settings->secretChats);
        $this->serialization->merge($settings->serialization);
        $this->schema->merge($settings->schema);
        $this->ipc->merge($settings->ipc);
        $this->templates->merge($settings->templates);
        $this->voip->merge($settings->voip);

        if (!$this->db instanceof $settings->db) {
            $this->db = $settings->db;
        } else {
            $this->db->merge($settings->db);
        }
    }

    /**
     * Get default DC ID.
     *
     * @return integer
     */
    public function getDefaultDc(): int
    {
        return $this->connection->getDefaultDc();
    }
    /**
     * Get default DC params.
     *
     * @return array
     */
    public function getDefaultDcParams(): array
    {
        return $this->connection->getDefaultDcParams();
    }

    /**
     * Set default DC ID.
     *
     * @param int $dc DC ID
     *
     * @return self
     */
    public function setDefaultDc(int $dc): self
    {
        $this->connection->setDefaultDc($dc);
        return $this;
    }

    /**
     * Get app information.
     *
     * @return AppInfo
     */
    public function getAppInfo(): AppInfo
    {
        return $this->appInfo;
    }

    /**
     * Set app information.
     *
     * @param AppInfo $appInfo App information.
     *
     * @return self
     */
    public function setAppInfo(AppInfo $appInfo): self
    {
        $this->appInfo = $appInfo;

        return $this;
    }

    /**
     * Get cryptography settings.
     *
     * @return Auth
     */
    public function getAuth(): Auth
    {
        return $this->auth;
    }

    /**
     * Set cryptography settings.
     *
     * @param Auth $auth Cryptography settings.
     *
     * @return self
     */
    public function setAuth(Auth $auth): self
    {
        $this->auth = $auth;

        return $this;
    }

    /**
     * Get connection settings.
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Set connection settings.
     *
     * @param Connection $connection Connection settings.
     *
     * @return self
     */
    public function setConnection(Connection $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Get file management settings.
     *
     * @return Files
     */
    public function getFiles(): Files
    {
        return $this->files;
    }

    /**
     * Set file management settings.
     *
     * @param Files $files File management settings.
     *
     * @return self
     */
    public function setFiles(Files $files): self
    {
        $this->files = $files;

        return $this;
    }

    /**
     * Get logger settings.
     *
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }

    /**
     * Set logger settings.
     *
     * @param Logger $logger Logger settings.
     *
     * @return self
     */
    public function setLogger(Logger $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Get peer database settings.
     *
     * @return Peer
     */
    public function getPeer(): Peer
    {
        return $this->peer;
    }

    /**
     * Set peer database settings.
     *
     * @param Peer $peer Peer database settings.
     *
     * @return self
     */
    public function setPeer(Peer $peer): self
    {
        $this->peer = $peer;

        return $this;
    }

    /**
     * Get PWRTelegram settings.
     *
     * @return Pwr
     */
    public function getPwr(): Pwr
    {
        return $this->pwr;
    }

    /**
     * Set PWRTelegram settings.
     *
     * @param Pwr $pwr PWRTelegram settings.
     *
     * @return self
     */
    public function setPwr(Pwr $pwr): self
    {
        $this->pwr = $pwr;

        return $this;
    }

    /**
     * Get RPC settings.
     *
     * @return RPC
     */
    public function getRpc(): RPC
    {
        return $this->rpc;
    }

    /**
     * Set RPC settings.
     *
     * @param RPC $rpc RPC settings.
     *
     * @return self
     */
    public function setRpc(RPC $rpc): self
    {
        $this->rpc = $rpc;

        return $this;
    }

    /**
     * Get secret chat settings.
     *
     * @return SecretChats
     */
    public function getSecretChats(): SecretChats
    {
        return $this->secretChats;
    }

    /**
     * Set secret chat settings.
     *
     * @param SecretChats $secretChats Secret chat settings.
     *
     * @return self
     */
    public function setSecretChats(SecretChats $secretChats): self
    {
        $this->secretChats = $secretChats;

        return $this;
    }

    /**
     * Get serialization settings.
     *
     * @return Serialization
     */
    public function getSerialization(): Serialization
    {
        return $this->serialization;
    }

    /**
     * Set serialization settings.
     *
     * @param Serialization $serialization Serialization settings.
     *
     * @return self
     */
    public function setSerialization(Serialization $serialization): self
    {
        $this->serialization = $serialization;

        return $this;
    }

    /**
     * Get TL schema settings.
     *
     * @return TLSchema
     */
    public function getSchema(): TLSchema
    {
        return $this->schema;
    }

    /**
     * Set TL schema settings.
     *
     * @param TLSchema $schema TL schema settings.
     *
     * @return self
     */
    public function setSchema(TLSchema $schema): self
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * Get database settings.
     *
     * @return DatabaseAbstract
     */
    public function getDb(): DatabaseAbstract
    {
        return $this->db;
    }

    /**
     * Set database settings.
     *
     * @param DatabaseAbstract $db DatabaseAbstract settings.
     *
     * @return self
     */
    public function setDb(DatabaseAbstract $db): self
    {
        $this->db = $db;

        return $this;
    }

    /**
     * Get IPC server settings.
     *
     * @return Ipc
     */
    public function getIpc(): Ipc
    {
        return $this->ipc;
    }

    /**
     * Set IPC server settings.
     *
     * @param Ipc $ipc IPC server settings.
     *
     * @return self
     */
    public function setIpc(Ipc $ipc): self
    {
        $this->ipc = $ipc;

        return $this;
    }

    public function applyChanges(): SettingsAbstract
    {
        foreach (\get_object_vars($this) as $setting) {
            if ($setting instanceof SettingsAbstract) {
                $setting->applyChanges();
            }
        }
        return $this;
    }

    /**
     * Get template settings.
     *
     * @return Templates
     */
    public function getTemplates(): Templates
    {
        return $this->templates;
    }

    /**
     * Set template settings.
     *
     * @param Templates $templates Template settings
     *
     * @return self
     */
    public function setTemplates(Templates $templates): self
    {
        $this->templates = $templates;

        return $this;
    }

    /**
     * Get voIP settings.
     *
     * @return VoIP
     */
    public function getVoip(): VoIP
    {
        return $this->voip;
    }

    /**
     * Set voIP settings.
     *
     * @param VoIP $voip VoIP settings.
     *
     * @return self
     */
    public function setVoip(VoIP $voip): self
    {
        $this->voip = $voip;

        return $this;
    }
}
