<?php declare(strict_types=1);

/**
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

use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Settings\Auth;
use danog\MadelineProto\Settings\Connection;
use danog\MadelineProto\Settings\Database\Memory as DatabaseMemory;
use danog\MadelineProto\Settings\DatabaseAbstract;
use danog\MadelineProto\Settings\Files;
use danog\MadelineProto\Settings\Ipc;
use danog\MadelineProto\Settings\Logger;
use danog\MadelineProto\Settings\Metrics;
use danog\MadelineProto\Settings\Peer;
use danog\MadelineProto\Settings\RPC;
use danog\MadelineProto\Settings\SecretChats;
use danog\MadelineProto\Settings\Serialization;
use danog\MadelineProto\Settings\Templates;
use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\Settings\VoIP;

/**
 * Settings class used for configuring MadelineProto.
 */
final class Settings extends SettingsAbstract
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
     * Metrics settings.
     */
    protected Metrics $metrics;
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
     * Constructor.
     */
    public function __construct()
    {
        $this->appInfo = new AppInfo;
        $this->auth = new Auth;
        $this->connection = new Connection;
        $this->files = new Files;
        $this->logger = new Logger;
        $this->peer = new Peer;
        $this->metrics = new Metrics;
        $this->rpc = new RPC;
        $this->secretChats = new SecretChats;
        $this->serialization = new Serialization;
        $this->schema = new TLSchema;
        $this->db = new DatabaseMemory;
        $this->templates = new Templates;
        $this->ipc = new IPc;
        $this->voip = new VoIP;
    }
    public function __wakeup(): void
    {
        if (!isset($this->voip)) {
            $this->voip = new VoIP;
        }
        if (!isset($this->metrics)) {
            $this->metrics = new Metrics;
        }
    }
    /**
     * Merge another instance of settings.
     *
     * @param SettingsAbstract $settings Settings
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
            } elseif ($settings instanceof Metrics) {
                $this->metrics->merge($settings);
            } elseif ($settings instanceof Logger) {
                $this->logger->merge($settings);
            } elseif ($settings instanceof Peer) {
                $this->peer->merge($settings);
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
        $this->metrics->merge($settings->metrics);
        $this->logger->merge($settings->logger);
        $this->peer->merge($settings->peer);
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
     * Get app information.
     */
    public function getAppInfo(): AppInfo
    {
        return $this->appInfo;
    }

    /**
     * Set app information.
     *
     * @param AppInfo $appInfo App information.
     */
    public function setAppInfo(AppInfo $appInfo): self
    {
        $this->appInfo = $appInfo;

        return $this;
    }

    /**
     * Get cryptography settings.
     */
    public function getAuth(): Auth
    {
        return $this->auth;
    }

    /**
     * Set cryptography settings.
     *
     * @param Auth $auth Cryptography settings.
     */
    public function setAuth(Auth $auth): self
    {
        $this->auth = $auth;

        return $this;
    }

    /**
     * Get connection settings.
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Set connection settings.
     *
     * @param Connection $connection Connection settings.
     */
    public function setConnection(Connection $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Get file management settings.
     */
    public function getFiles(): Files
    {
        return $this->files;
    }

    /**
     * Set file management settings.
     *
     * @param Files $files File management settings.
     */
    public function setFiles(Files $files): self
    {
        $this->files = $files;

        return $this;
    }

    /**
     * Get metrics settings.
     */
    public function getMetrics(): Metrics
    {
        return $this->metrics;
    }

    /**
     * Set metrics settings.
     *
     * @param Metrics $metrics File management settings.
     */
    public function setMetrics(Metrics $metrics): self
    {
        $this->metrics = $metrics;

        return $this;
    }

    /**
     * Get logger settings.
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }

    /**
     * Set logger settings.
     *
     * @param Logger $logger Logger settings.
     */
    public function setLogger(Logger $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Get peer database settings.
     */
    public function getPeer(): Peer
    {
        return $this->peer;
    }

    /**
     * Set peer database settings.
     *
     * @param Peer $peer Peer database settings.
     */
    public function setPeer(Peer $peer): self
    {
        $this->peer = $peer;

        return $this;
    }

    /**
     * Get RPC settings.
     */
    public function getRpc(): RPC
    {
        return $this->rpc;
    }

    /**
     * Set RPC settings.
     *
     * @param RPC $rpc RPC settings.
     */
    public function setRpc(RPC $rpc): self
    {
        $this->rpc = $rpc;

        return $this;
    }

    /**
     * Get secret chat settings.
     */
    public function getSecretChats(): SecretChats
    {
        return $this->secretChats;
    }

    /**
     * Set secret chat settings.
     *
     * @param SecretChats $secretChats Secret chat settings.
     */
    public function setSecretChats(SecretChats $secretChats): self
    {
        $this->secretChats = $secretChats;

        return $this;
    }

    /**
     * Get serialization settings.
     */
    public function getSerialization(): Serialization
    {
        return $this->serialization;
    }

    /**
     * Set serialization settings.
     *
     * @param Serialization $serialization Serialization settings.
     */
    public function setSerialization(Serialization $serialization): self
    {
        $this->serialization = $serialization;

        return $this;
    }

    /**
     * Get TL schema settings.
     */
    public function getSchema(): TLSchema
    {
        return $this->schema;
    }

    /**
     * Set TL schema settings.
     *
     * @param TLSchema $schema TL schema settings.
     */
    public function setSchema(TLSchema $schema): self
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * Get database settings.
     */
    public function getDb(): DatabaseAbstract
    {
        return $this->db;
    }

    /**
     * Set database settings.
     *
     * @param DatabaseAbstract $db DatabaseAbstract settings.
     */
    public function setDb(DatabaseAbstract $db): self
    {
        $this->db = $db;

        return $this;
    }

    /**
     * Get IPC server settings.
     */
    public function getIpc(): Ipc
    {
        return $this->ipc;
    }

    /**
     * Set IPC server settings.
     *
     * @param Ipc $ipc IPC server settings.
     */
    public function setIpc(Ipc $ipc): self
    {
        $this->ipc = $ipc;

        return $this;
    }

    public function applyChanges(): SettingsAbstract
    {
        foreach (get_object_vars($this) as $setting) {
            if ($setting instanceof SettingsAbstract) {
                $setting->applyChanges();
            }
        }
        return $this;
    }

    /**
     * Get template settings.
     */
    public function getTemplates(): Templates
    {
        return $this->templates;
    }

    /**
     * Set template settings.
     *
     * @param Templates $templates Template settings
     */
    public function setTemplates(Templates $templates): self
    {
        $this->templates = $templates;

        return $this;
    }

    /**
     * Get voIP settings.
     */
    public function getVoip(): VoIP
    {
        return $this->voip;
    }

    /**
     * Set voIP settings.
     *
     * @param VoIP $voip VoIP settings.
     */
    public function setVoip(VoIP $voip): self
    {
        $this->voip = $voip;

        return $this;
    }
}
