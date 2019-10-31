<?php

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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

use danog\MadelineProto\MTProto;
use danog\MadelineProto\TL\TLCallback;
use danog\MadelineProto\Tools;

/**
 * Manages min peers.
 */
class MinDatabase implements TLCallback
{
    use Tools;

    const SWITCH_CONSTRUCTORS = [
        'inputChannel',
        'inputUser',
        'inputPeerUser',
        'inputPeerChannel',
    ];
    const CATCH_PEERS = [
        'message',
        'messageService',
        'peerUser',
        'peerChannel',
        'messageEntityMentionName',

        'messageFwdHeader',
        'messageActionChatCreate',
        'messageActionChatAddUser',
        'messageActionChatDeleteUser',
        'messageActionChatJoinedByLink',
    ];
    const ORIGINS = ['message', 'messageService'];
    /**
     * References indexed by location.
     *
     * @var array
     */
    private $db = [];
    /**
     * Temporary cache during deserialization.
     *
     * @var array
     */
    private $cache = [];
    /**
     * Instance of MTProto.
     *
     * @var \danog\MadelineProto\MTProto
     */
    private $API;

    public function __construct(MTProto $API)
    {
        $this->API = $API;
        $this->init();
    }

    public function __wakeup()
    {
        $this->init();
    }

    public function __sleep()
    {
        return ['db', 'API'];
    }

    public function init()
    {
        foreach ($this->db as $id => $origin) {
            if (!isset($origin['peer']) || $origin['peer'] === $id) {
                unset($this->db[$id]);
            }
        }
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
            \array_fill_keys(self::CATCH_PEERS, [[$this, 'addPeer']]),
            \array_fill_keys(self::ORIGINS, [[$this, 'addOrigin']])
        );
    }

    public function getConstructorBeforeCallbacks(): array
    {
        return \array_fill_keys(self::ORIGINS, [[$this, 'addOriginContext']]);
    }

    public function getConstructorSerializeCallbacks(): array
    {
        return \array_fill_keys(self::SWITCH_CONSTRUCTORS, [$this, 'populateFrom']);
    }

    public function getTypeMismatchCallbacks(): array
    {
        return [];
    }

    public function reset()
    {
        if ($this->cache) {
            $this->API->logger->logger('Found '.\count($this->cache).' pending contexts', \danog\MadelineProto\Logger::ERROR);
            $this->cache = [];
        }
    }

    public function addPeer(array $location)
    {
        if (!$this->cache) {
            return;
            $this->API->logger->logger('Trying to add peer out of context, report the following message to @danogentili!', \danog\MadelineProto\Logger::ERROR);
            $frames = [];
            $previous = '';
            foreach (\debug_backtrace(0) as $k => $frame) {
                if (isset($frame['function']) && $frame['function'] === 'deserialize') {
                    if (isset($frame['args'][1]['subtype'])) {
                        if ($frame['args'][1]['subtype'] === $previous) {
                            continue;
                        }

                        $frames[] = $frame['args'][1]['subtype'];
                        $previous = $frame['args'][1]['subtype'];
                    } elseif (isset($frame['args'][1]['type'])) {
                        if ($frame['args'][1]['type'] === '') {
                            break;
                        }

                        if ($frame['args'][1]['type'] === $previous) {
                            continue;
                        }

                        $frames[] = $frame['args'][1]['type'];
                        $previous = $frame['args'][1]['type'];
                    }
                }
            }
            $frames = \array_reverse($frames);
            $tl_trace = \array_shift($frames);
            foreach ($frames as $frame) {
                $tl_trace .= "['".$frame."']";
            }
            $this->API->logger->logger($tl_trace, \danog\MadelineProto\Logger::ERROR);

            return false;
        }
        $peers = [];
        switch ($location['_']) {
            case 'messageFwdHeader':
                if (isset($location['from_id'])) {
                    $peers[$location['from_id']] = true;
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
                $peers[$this->API->getId($location['to_id'])] = true;
                if (isset($location['from_id'])) {
                    $peers[$location['from_id']] = true;
                }
                break;
            default:
                $peers[$this->API->getId($location)] = true;
        }
        $this->API->logger->logger("Caching peer location info from location from {$location['_']}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        $key = \count($this->cache) - 1;
        foreach ($peers as $id => $true) {
            $this->cache[$key][$id] = $id;
        }

        return true;
    }

    public function addOriginContext(string $type)
    {
        $this->API->logger->logger("Adding peer origin context for {$type}!", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        $this->cache[] = [];
    }

    public function addOrigin(array $data = [])
    {
        $cache = \array_pop($this->cache);
        if ($cache === null) {
            throw new \danog\MadelineProto\Exception('Trying to add origin with no origin context set');
        }
        $origin = [];
        switch ($data['_']) {
            case 'message':
            case 'messageService':
                $origin['peer'] = $this->API->getId($data);
                $origin['msg_id'] = $data['id'];
                break;
            default:
                throw new \danog\MadelineProto\Exception("Unknown origin type provided: {$data['_']}");
        }
        foreach ($cache as $id) {
            if ($origin['peer'] === $id) {
                continue;
            }
            $this->db[$id] = $origin;
        }
        $this->API->logger->logger("Added origin ({$data['_']}) to ".\count($cache).' peer locations', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
    }

    public function populateFrom(array $object)
    {
        if (!($object['min'] ?? false)) {
            return $object;
        }
        $id = $this->API->getId($object);
        if (isset($this->db[$id])) {
            $new = \array_merge($object, $this->db[$id]);
            $new['_'] .= 'FromMessage';
            $new['peer'] = (yield $this->API->getInfo($new['peer']))['InputPeer'];
            if ($new['peer']['min']) {
                $this->API->logger->logger("Don't have origin peer subinfo with min peer $id, this may fail");
                return $object;
            }
            return $new;
        }
        $this->API->logger->logger("Don't have origin info with min peer $id, this may fail");


        return $object;
    }

    /**
     * Check if location info is available for peer.
     *
     * @param float|int $id Peer ID
     *
     * @return boolean
     */
    public function hasPeer($id): bool
    {
        return isset($this->db[$id]);
    }
    public function __debugInfo()
    {
        return ['MinDatabase instance '.\spl_object_hash($this)];
    }
}
