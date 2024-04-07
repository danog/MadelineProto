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

use Amp\Sync\LocalKeyedMutex;
use danog\AsyncOrm\Annotations\OrmMappedArray;
use danog\AsyncOrm\DbArray;
use danog\AsyncOrm\KeyType;
use danog\AsyncOrm\ValueType;
use danog\MadelineProto\Exception;
use danog\MadelineProto\LegacyMigrator;
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
    use LegacyMigrator;

    public const SWITCH_CONSTRUCTORS = ['inputChannel', 'inputUser', 'inputPeerUser', 'inputPeerChannel'];
    public const CATCH_PEERS = ['message', 'messageService', 'peerUser', 'peerChannel', 'messageEntityMentionName', 'messageFwdHeader', 'messageActionChatCreate', 'messageActionChatAddUser', 'messageActionChatDeleteUser', 'messageActionChatJoinedByLink'];
    public const ORIGINS = ['message', 'messageService'];
    private const V = 1;
    /**
     * References indexed by location.
     *
     * @var DbArray<int, array>
     */
    #[OrmMappedArray(KeyType::INT, ValueType::SCALAR)]
    private $db;
    private array $pendingDb = [];
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

    private int $v = 0;

    private LocalKeyedMutex $localMutex;
    public function __construct(MTProto $API)
    {
        $this->API = $API;
        $this->v = self::V;
        $this->localMutex = new LocalKeyedMutex;
    }
    public function __destruct()
    {
    }
    public function __sleep()
    {
        return ['db', 'pendingDb', 'API', 'v'];
    }
    public function __wakeup(): void
    {
        $this->localMutex = new LocalKeyedMutex;
    }
    public function init(): void
    {
        $this->initDbProperties($this->API->getDbSettings(), $this->API->getDbPrefix().'_MinDatabase_');
        if (!$this->API->getSettings()->getDb()->getEnableMinDb()) {
            $this->db->clear();
            $this->pendingDb = [];
            return;
        }

        if ($this->v !== self::V) {
            $this->db->clear();
            $this->pendingDb = [];
            $this->v = self::V;
        }

        EventLoop::queue(function (): void {
            $this->API->waitForInit();
            foreach ($this->pendingDb as $key => $_) {
                EventLoop::queue($this->flush(...), $key);
            }
        });
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
        return array_merge(array_fill_keys(self::CATCH_PEERS, [$this->addPeer(...)]), array_fill_keys(self::ORIGINS, [$this->addOrigin(...)]));
    }
    public function getConstructorBeforeDeserializationCallbacks(): array
    {
        return array_fill_keys(self::ORIGINS, [$this->addOriginContext(...)]);
    }
    public function getConstructorBeforeSerializationCallbacks(): array
    {
        return array_fill_keys(self::SWITCH_CONSTRUCTORS, $this->populateFrom(...));
    }
    public function getTypeMismatchCallbacks(): array
    {
        return [];
    }
    public function reset(): void
    {
        if ($this->cache) {
            $this->API->logger('Found '.\count($this->cache).' pending contexts', Logger::ERROR);
            $this->cache = [];
        }
    }
    public function addPeer(array|int $location): bool
    {
        $peers = [];
        switch ($location['_']) {
            case 'messageFwdHeader':
                if (isset($location['from_id'])) {
                    $peers[$location['from_id']] = true;
                }
                if (isset($location['channel_id'])) {
                    $peers[$location['channel_id']] = true;
                }
                break;
            case 'messageActionChatCreate':
            case 'messageActionChatAddUser':
                foreach ($location['users'] as $user) {
                    $peers[$user] = true;
                }
                break;
            case 'message':
                $peers[$location['peer_id']] = true;
                if (isset($location['from_id'])) {
                    $peers[$location['from_id']] = true;
                }
                break;
            default:
                $peers[$this->API->getIdInternal($location)] = true;
        }
        $this->API->logger("Caching peer location info from location from {$location['_']}", Logger::ULTRA_VERBOSE);
        $key = \count($this->cache) - 1;
        foreach ($peers as $id => $true) {
            $this->cache[$key][$id] = $id;
        }
        return true;
    }
    public function addOriginContext(string $type): void
    {
        $this->API->logger("Adding peer origin context for {$type}!", Logger::ULTRA_VERBOSE);
        $this->cache[] = [];
    }
    public function addOrigin(array $data = []): void
    {
        $cache = array_pop($this->cache);
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
            $this->pendingDb[$id] = $origin;
            EventLoop::queue($this->flush(...), $id);
        }
        $this->API->logger("Added origin ({$data['_']}) to ".\count($cache).' peer locations', Logger::ULTRA_VERBOSE);
    }
    private function flush(int $id): void
    {
        if (!isset($this->pendingDb[$id])) {
            return;
        }
        $lock = $this->localMutex->acquire((string) $id);
        try {
            if (!isset($this->pendingDb[$id])) {
                return;
            }
            if ($this->API->peerDatabase->get($id)['min'] ?? true) {
                $this->db[$id] = $this->pendingDb[$id];
            }
        } finally {
            unset($this->pendingDb[$id]);
            EventLoop::queue($lock->release(...));
        }
    }
    public function populateFrom(array $object)
    {
        if (!($object['min'] ?? false)) {
            return $object;
        }
        $id = $this->API->getIdInternal($object);
        $dbObject = $this->pendingDb[$id] ?? $this->db[$id];
        if ($dbObject) {
            $new = array_merge($object, $dbObject);
            $new['_'] .= 'FromMessage';
            $new['peer'] = $this->API->getInputPeer($new['peer']);
            if (($new['peer']['min'] ?? false)) {
                $this->API->logger("Don't have origin peer subinfo with min peer {$id}, this may fail");
                return $object;
            }
            return $new;
        }
        $this->API->logger("Don't have origin info with min peer {$id}, this may fail");
        return $object;
    }

    /**
     * Check if location info is available for peer.
     *
     * @param int $id Peer ID
     */
    public function hasPeer(int $id): bool
    {
        return isset($this->pendingDb[$id]) || isset($this->db[$id]);
    }
    /**
     * Remove location info for peer.
     */
    public function clearPeer(int $id): void
    {
        unset($this->db[$id], $this->pendingDb[$id]);
    }
    public function __debugInfo()
    {
        return ['MinDatabase instance '.spl_object_hash($this)];
    }
}
