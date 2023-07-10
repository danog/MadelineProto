<?php

declare(strict_types=1);

/**
 * Files module.
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

namespace danog\MadelineProto\MTProtoTools;

use danog\MadelineProto\Db\DbArray;
use danog\MadelineProto\Db\DbPropertiesTrait;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\TL\TLCallback;
use Revolt\EventLoop;

/**
 * Manages min peers.
 *
 * @internal
 */
final class MinDatabase implements TLCallback
{
    use DbPropertiesTrait;

    const SWITCH_CONSTRUCTORS = ['inputChannel', 'inputUser', 'inputPeerUser', 'inputPeerChannel'];
    const CATCH_PEERS = ['message', 'messageService', 'peerUser', 'peerChannel', 'messageEntityMentionName', 'messageFwdHeader', 'messageActionChatCreate', 'messageActionChatAddUser', 'messageActionChatDeleteUser', 'messageActionChatJoinedByLink'];
    const ORIGINS = ['message', 'messageService'];
    /**
     * References indexed by location.
     *
     */
    private DbArray $db;
    /**
     * Temporary cache during deserialization.
     *
     */
    private array $cache = [];
    /**
     * Instance of MTProto.
     *
     */
    private MTProto $API;

    /**
     * Whether we cleaned up old database information.
     *
     */
    private bool $clean = false;
    /**
     * Whether we synced ourselves with the peer database.
     *
     */
    private bool $synced = false;

    /**
     * List of properties stored in database (memory or external).
     *
     * @see DbPropertiesFactory
     */
    protected static array $dbProperties = [
        'db' => ['innerMadelineProto' => true],
    ];

    public function __construct(MTProto $API)
    {
        $this->API = $API;
    }
    public function __sleep()
    {
        return ['db', 'API', 'clean', 'synced'];
    }
    public function init(): void
    {
        $this->initDb($this->API);
        if (!$this->API->getSettings()->getDb()->getEnableMinDb()) {
            $this->db->clear();
        }
        if (!$this->clean) {
            EventLoop::queue(function (): void {
                foreach ($this->db as $id => $origin) {
                    if (!isset($origin['peer']) || $origin['peer'] === $id) {
                        unset($this->db[$id]);
                    }
                }
                $this->clean = true;
            });
        }
    }
    public function sync(): void
    {
        if (!$this->synced) {
            EventLoop::queue(function (): void {
                $counter = 0;
                foreach ($this->API->chats as $id => $chat) {
                    $id = (int) $id;
                    $counter++;
                    if ($counter % 1000 === 0) {
                        $this->API->logger->logger("Upgrading chats: $counter", Logger::WARNING);
                    }

                    if (!($chat['min'] ?? false)) {
                        $this->clearPeer($id);
                    }
                }
                $this->synced = true;
            });
        }
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
        return \array_merge(\array_fill_keys(self::CATCH_PEERS, [$this->addPeer(...)]), \array_fill_keys(self::ORIGINS, [$this->addOrigin(...)]));
    }
    public function getConstructorBeforeDeserializationCallbacks(): array
    {
        return \array_fill_keys(self::ORIGINS, [$this->addOriginContext(...)]);
    }
    public function getConstructorBeforeSerializationCallbacks(): array
    {
        return \array_fill_keys(self::SWITCH_CONSTRUCTORS, $this->populateFrom(...));
    }
    public function getTypeMismatchCallbacks(): array
    {
        return [];
    }
    public function areDeserializationCallbacksMutuallyExclusive(): bool
    {
        return true;
    }
    public function reset(): void
    {
        if ($this->cache) {
            $this->API->logger->logger('Found '.\count($this->cache).' pending contexts', Logger::ERROR);
            $this->cache = [];
        }
    }
    public function addPeer(array $location): bool
    {
        $peers = [];
        switch ($location['_']) {
            case 'messageFwdHeader':
                if (isset($location['from_id'])) {
                    $peers[$this->API->getIdInternal($location['from_id'])] = true;
                }
                if (isset($location['channel_id'])) {
                    $peers[$this->API->toSupergroup($location['channel_id'])] = true;
                }
                break;
            case 'messageActionChatCreate':
            case 'messageActionChatAddUser':
                foreach ($location['users'] as $user) {
                    $peers[$user] = true;
                }
                break;
            case 'message':
                $peers[$this->API->getIdInternal($location['peer_id'])] = true;
                if (isset($location['from_id'])) {
                    $peers[$this->API->getIdInternal($location['from_id'])] = true;
                }
                break;
            default:
                $peers[$this->API->getIdInternal($location)] = true;
        }
        $this->API->logger->logger("Caching peer location info from location from {$location['_']}", Logger::ULTRA_VERBOSE);
        $key = \count($this->cache) - 1;
        foreach ($peers as $id => $true) {
            $this->cache[$key][$id] = $id;
        }
        return true;
    }
    public function addOriginContext(string $type): void
    {
        $this->API->logger->logger("Adding peer origin context for {$type}!", Logger::ULTRA_VERBOSE);
        $this->cache[] = [];
    }
    public function addOrigin(array $data = []): void
    {
        $cache = \array_pop($this->cache);
        if ($cache === null) {
            throw new Exception('Trying to add origin with no origin context set');
        }
        $origin = [];
        switch ($data['_']) {
            case 'message':
            case 'messageService':
                $origin['peer'] = $this->API->getIdInternal($data);
                $origin['msg_id'] = $data['id'];
                break;
            default:
                throw new Exception("Unknown origin type provided: {$data['_']}");
        }
        foreach ($cache as $id) {
            if ($origin['peer'] === $id) {
                continue;
            }
            $this->db[$id] = $origin;
        }
        $this->API->logger->logger("Added origin ({$data['_']}) to ".\count($cache).' peer locations', Logger::ULTRA_VERBOSE);
    }
    public function populateFrom(array $object)
    {
        if (!($object['min'] ?? false)) {
            return $object;
        }
        $id = $this->API->getIdInternal($object);
        $dbObject = $this->db[$id];
        if ($dbObject) {
            $new = \array_merge($object, $dbObject);
            $new['_'] .= 'FromMessage';
            $new['peer'] = $this->API->getInputPeer($new['peer']);
            if ($new['peer']['min']) {
                $this->API->logger->logger("Don't have origin peer subinfo with min peer {$id}, this may fail");
                return $object;
            }
            return $new;
        }
        $this->API->logger->logger("Don't have origin info with min peer {$id}, this may fail");
        return $object;
    }

    /**
     * Check if location info is available for peer.
     *
     * @param int $id Peer ID
     */
    public function hasPeer(int $id): bool
    {
        return isset($this->db[$id]);
    }
    /**
     * Remove location info for peer.
     */
    public function clearPeer(int $id): void
    {
        unset($this->db[$id]);
    }
    public function __debugInfo()
    {
        return ['MinDatabase instance '.\spl_object_hash($this)];
    }
}
