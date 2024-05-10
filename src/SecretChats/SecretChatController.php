<?php

declare(strict_types=1);

/**
 * Secret chat module.
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

namespace danog\MadelineProto\SecretChats;

use Amp\Future;
use Amp\Sync\LocalKeyedMutex;
use Amp\Sync\LocalMutex;
use AssertionError;
use danog\AsyncOrm\Annotations\OrmMappedArray;
use danog\AsyncOrm\DbArray;
use danog\AsyncOrm\KeyType;
use danog\AsyncOrm\ValueType;
use danog\DialogId\DialogId;
use danog\MadelineProto\LegacyMigrator;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\Secret\SecretFeedLoop;
use danog\MadelineProto\Loop\Update\UpdateLoop;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\ResponseException;
use danog\MadelineProto\SecurityException;
use danog\MadelineProto\Tools;
use phpseclib3\Math\BigInteger;
use Revolt\EventLoop;
use Stringable;
use Webmozart\Assert\Assert;

/**
 * Represents a secret chat.
 * @internal
 *
 * @psalm-type TKey=array{auth_key: string, fingerprint: string, visualization_orig: string, visualization_46: string}
 */
final class SecretChatController implements Stringable
{
    use LegacyMigrator;

    /**
     * @var DbArray<int, array>
     */
    #[OrmMappedArray(KeyType::INT, ValueType::SCALAR)]
    private $incoming;
    /**
     * @var DbArray<int, array>
     */
    #[OrmMappedArray(KeyType::INT, ValueType::SCALAR)]
    private $outgoing;
    /**
     * @var DbArray<int, list{int, bool}> Seq, outgoing
     */
    #[OrmMappedArray(KeyType::INT, ValueType::SCALAR)]
    private $randomIdMap;
    private int $in_seq_no = 0;
    private int $out_seq_no = 0;
    private int $remote_in_seq_no = 0;
    private int $remoteLayer = 46;
    private int $updated;

    private RekeyState $rekeyState = RekeyState::IDLE;
    private ?int $rekeyExchangeId = null;
    private ?BigInteger $rekeyParam = null;
    /** @var ?TKey */
    private ?array $rekeyKey = null;

    /** @var ?TKey */
    private ?array $oldKey = null;

    private int $ttr = 100;

    private int $mtproto = 1;

    /** @var 0|1 */
    private int $in_seq_no_base;
    /** @var 0|1 */
    private int $out_seq_no_base;

    public readonly array $inputChat;
    private int $ttl = 0;

    private SecretFeedLoop $feedLoop;
    public readonly SecretChat $public;
    private LocalKeyedMutex $sentMutex;
    public function __construct(
        private readonly MTProto $API,
        /** @var TKey */
        private array $key,
        public readonly int $id,
        int $accessHash,
        bool $creator,
        int $otherID,
    ) {
        $this->inputChat = [
            '_' => 'inputEncryptedChat',
            'chat_id' => $id,
            'access_hash' => $accessHash,
        ];
        if ($creator) {
            $this->in_seq_no_base = 0;
            $this->out_seq_no_base = 1;
        } else {
            $this->in_seq_no_base = 1;
            $this->out_seq_no_base = 0;
        }
        $this->public = new SecretChat(
            $API,
            DialogId::fromSecretChatId($id),
            $creator,
            $otherID,
        );
        $this->updated = $this->public->created;
        $this->feedLoop = new SecretFeedLoop($API, $this);
        $this->feedLoop->start();
        $this->rekeyMutex = new LocalMutex;
        $this->encryptMutex = new LocalMutex;
        $this->sentMutex = new LocalKeyedMutex;
        $this->init();
    }

    public function feed(array $update): void
    {
        $this->feedLoop->feed($update);
    }
    public function init(): void
    {
        $this->initDbProperties(
            $this->API->getDbSettings(),
            $this->API->getDbPrefix().'_SecretChatController_'.$this->id.'_'
        );
    }

    public function startFeedLoop(): void
    {
        $this->feedLoop->start();
    }

    public function __serialize(): array
    {
        $vars = get_object_vars($this);
        unset($vars['rekeyMutex'], $vars['encryptMutex'], $vars['sentMutex']);

        return $vars;
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
        $this->rekeyMutex = new LocalMutex;
        $this->encryptMutex = new LocalMutex;
        $this->sentMutex = new LocalKeyedMutex;
    }

    /**
     * Discard secret chat.
     */
    public function discard(): void
    {
        $this->API->discardSecretChat($this->id);
    }
    public function notifyLayer(): void
    {
        $this->API->methodCallAsyncRead('messages.sendEncryptedService', ['peer' => $this->id, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionNotifyLayer', 'layer' => $this->API->getTL()->getSecretLayer()]]]);
    }
    private LocalMutex $rekeyMutex;
    /**
     * Rekey secret chat.
     */
    private function rekey(): void
    {
        if ($this->rekeyState !== RekeyState::IDLE) {
            return;
        }
        $lock = $this->rekeyMutex->acquire();
        try {
            if ($this->rekeyState !== RekeyState::IDLE) {
                return;
            }
            $dh_config = $this->API->getDhConfig();
            $this->API->logger('Rekeying secret chat '.$this.'...', Logger::VERBOSE);
            $this->API->logger('Generating a...', Logger::VERBOSE);
            $a = new BigInteger(Tools::random(256), 256);
            $this->API->logger('Generating g_a...', Logger::VERBOSE);
            $g_a = $dh_config['g']->powMod($a, $dh_config['p']);
            Crypt::checkG($g_a, $dh_config['p']);
            $this->rekeyState = RekeyState::REQUESTED;
            $this->rekeyExchangeId = Tools::randomInt();
            $this->rekeyParam = $a;
            $this->API->methodCallAsyncRead('messages.sendEncryptedService', ['peer' => $this->id, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionRequestKey', 'g_a' => $g_a->toBytes(), 'exchange_id' => $this->rekeyExchangeId]]]);
            $this->API->updaters[UpdateLoop::GENERIC]->resume();
        } finally {
            EventLoop::queue($lock->release(...));
        }
    }
    /**
     * Accept rekeying.
     *
     * @param array $params Parameters
     */
    private function acceptRekey(array $params): void
    {
        $lock = $this->rekeyMutex->acquire();
        try {
            if ($this->rekeyState !== RekeyState::IDLE) {
                if ($this->rekeyExchangeId > $params['exchange_id']) {
                    return;
                }
                if ($this->rekeyExchangeId === $params['exchange_id']) {
                    $this->rekeyState = RekeyState::IDLE;
                    return;
                }
            }
            $this->API->logger('Accepting rekeying of '.$this.'...', Logger::VERBOSE);
            $dh_config = $this->API->getDhConfig();
            $this->API->logger('Generating b...', Logger::VERBOSE);
            $b = new BigInteger(Tools::random(256), 256);
            $params['g_a'] = new BigInteger((string) $params['g_a'], 256);
            Crypt::checkG($params['g_a'], $dh_config['p']);
            $key = ['auth_key' => str_pad($params['g_a']->powMod($b, $dh_config['p'])->toBytes(), 256, \chr(0), STR_PAD_LEFT)];
            $key['fingerprint'] = substr(sha1($key['auth_key'], true), -8);
            $key['visualization_orig'] = $this->key['visualization_orig'];
            $key['visualization_46'] = substr(hash('sha256', $key['auth_key'], true), 20);

            $this->rekeyState = RekeyState::ACCEPTED;
            $this->rekeyExchangeId = $params['exchange_id'];
            $this->rekeyKey = $key;

            $g_b = $dh_config['g']->powMod($b, $dh_config['p']);
            Crypt::checkG($g_b, $dh_config['p']);
            $this->API->methodCallAsyncRead('messages.sendEncryptedService', ['peer' => $this->id, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionAcceptKey', 'g_b' => $g_b->toBytes(), 'exchange_id' => $params['exchange_id'], 'key_fingerprint' => $key['fingerprint']]]]);
            $this->API->updaters[UpdateLoop::GENERIC]->resume();
        } finally {
            EventLoop::queue($lock->release(...));
        }
    }
    /**
     * Commit rekeying of secret chat.
     *
     * @param array $params Parameters
     */
    private function commitRekey(array $params): void
    {
        $lock = $this->rekeyMutex->acquire();
        try {
            if ($this->rekeyState !== RekeyState::REQUESTED || $this->rekeyExchangeId !== $params['exchange_id']) {
                $this->rekeyState = RekeyState::IDLE;
                return;
            }
            $this->API->logger('Committing rekeying of '.$this.'...', Logger::VERBOSE);
            $dh_config = ($this->API->getDhConfig());
            $params['g_b'] = new BigInteger((string) $params['g_b'], 256);
            Crypt::checkG($params['g_b'], $dh_config['p']);
            \assert($this->rekeyParam !== null);
            $key = ['auth_key' => str_pad($params['g_b']->powMod($this->rekeyParam, $dh_config['p'])->toBytes(), 256, \chr(0), STR_PAD_LEFT)];
            $key['fingerprint'] = substr(sha1($key['auth_key'], true), -8);
            $key['visualization_orig'] = $this->key['visualization_orig'];
            $key['visualization_46'] = substr(hash('sha256', $key['auth_key'], true), 20);
            if ($key['fingerprint'] !== $params['key_fingerprint']) {
                $this->API->methodCallAsyncRead('messages.sendEncryptedService', ['peer' => $this->id, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionAbortKey', 'exchange_id' => $params['exchange_id']]]]);
                throw new SecurityException('Invalid key fingerprint!');
            }
            $this->API->methodCallAsyncRead('messages.sendEncryptedService', ['peer' => $this->id, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionCommitKey', 'exchange_id' => $params['exchange_id'], 'key_fingerprint' => $key['fingerprint']]]]);
            $this->rekeyState = RekeyState::IDLE;
            $this->oldKey = $this->key;
            $this->key = $key;
            $this->ttr = 100;
            $this->updated = time();
            $this->API->updaters[UpdateLoop::GENERIC]->resume();
        } finally {
            EventLoop::queue($lock->release(...));
        }
    }
    /**
     * Complete rekeying.
     *
     * @param array $params Parameters
     */
    private function completeRekey(array $params): void
    {
        $lock = $this->rekeyMutex->acquire();
        try {
            if ($this->rekeyState !== RekeyState::ACCEPTED || $this->rekeyExchangeId !== $params['exchange_id']) {
                return;
            }
            \assert($this->rekeyKey !== null);
            if ($this->rekeyKey['fingerprint'] !== $params['key_fingerprint']) {
                $this->API->methodCallAsyncRead('messages.sendEncryptedService', ['peer' => $this->id, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionAbortKey', 'exchange_id' => $params['exchange_id']]]]);
                throw new SecurityException('Invalid key fingerprint!');
            }
            $this->API->logger('Completing rekeying of secret chat '.$this.'...', Logger::VERBOSE);
            $this->rekeyState = RekeyState::IDLE;
            $this->oldKey = $this->key;
            $this->key = $this->rekeyKey;
            $this->ttr = 100;
            $this->updated = time();
            $this->API->methodCallAsyncRead('messages.sendEncryptedService', ['peer' => $this->id, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionNoop']]]);
            $this->API->logger('Secret chat '.$this.' rekeyed successfully!', Logger::VERBOSE);
        } finally {
            EventLoop::queue($lock->release(...));
        }
    }

    private LocalMutex $encryptMutex;
    /**
     * Encrypt secret chat message.
     * @internal
     */
    public function encryptSecretMessage(array $body, Future $promise): array
    {
        $body['peer'] = $this->inputChat;
        if (isset($body['data'])) {
            return $body;
        }

        $lock = $this->encryptMutex->acquire();
        try {
            $this->ttr--;
            if ($this->remoteLayer > 8
                && ($this->ttr <= 0 || time() - $this->updated > 7 * 24 * 60 * 60)
                && $this->rekeyState === RekeyState::IDLE
            ) {
                EventLoop::queue($this->rekey(...));
            }

            $this->encryptSecretMessageInner($body);

            $promise->finally($lock->release(...));
            return $body;
        } catch (\Throwable $e) {
            $lock->release();
            throw $e;
        }
    }
    private function encryptSecretMessageInner(array &$body): void
    {
        $message = $body['message'];
        $randomId = $message['random_id'] = Tools::randomInt();
        Assert::true($this->remoteLayer > 8);
        $body['_'] = 'updateNewOutgoingEncryptedMessage';
        $body['message'] = [
            '_' => $body['method'] === 'messages.sendEncryptedService'
                ? 'encryptedMessageService'
                : 'encryptedMessage',
            'message' => $message,
            'chat_id' => $this->id,
        ]; // Not sent
        $message = ['_' => 'decryptedMessageLayer', 'layer' => $this->remoteLayer, 'in_seq_no' => $this->generateSecretInSeqNo(), 'out_seq_no' => $this->generateSecretOutSeqNo(), 'message' => $message];
        $body['seq'] = $seq = $this->out_seq_no++; // Not sent
        $constructor = $this->remoteLayer === 8 ? 'DecryptedMessage' : 'DecryptedMessageLayer';
        $message = $this->API->getTL()->serializeObject(['type' => $constructor], $message, $constructor, $this->remoteLayer);
        $message = Tools::packUnsignedInt(\strlen($message)).$message;
        if ($this->mtproto === 2) {
            $padding = Tools::posmod(-\strlen($message), 16);
            if ($padding < 12) {
                $padding += 16;
            }
            $message .= Tools::random($padding);
            $message_key = substr(hash('sha256', substr($this->key['auth_key'], 88 + ($this->public->creator ? 0 : 8), 32).$message, true), 8, 16);
            [$aes_key, $aes_iv] = Crypt::kdf($message_key, $this->key['auth_key'], $this->public->creator);
        } else {
            $message_key = substr(sha1($message, true), -16);
            [$aes_key, $aes_iv] = Crypt::oldKdf($message_key, $this->key['auth_key'], true);
            $message .= Tools::random(Tools::posmod(-\strlen($message), 16));
        }
        $body['data'] = $this->key['fingerprint'].$message_key.Crypt::igeEncrypt($message, $aes_key, $aes_iv);
        $this->outgoing[$seq] = $body;
        $this->randomIdMap[$randomId] = [$seq, true];
    }

    public function handleSent(array $request, array $response): array
    {
        $lock = $this->sentMutex->acquire((string) $request['seq']);
        try {
            $msg = $this->outgoing[$request['seq']];
            if (!isset($msg['message']['date'])) {
                $msg['message']['date'] = $response['date'];
                $msg['message']['decrypted_message'] = $msg['message']['message'];
                unset($msg['message']['message']);
                if (isset($msg['message']['decrypted_message']['media'])
                    && $msg['message']['decrypted_message']['media']['_'] !== 'decryptedMessageMediaEmpty'
                ) {
                    $msg['message']['file'] = $response['file'];
                    $msg['message']['decrypted_message']['media']['file'] = $msg['message']['file'];
                    $msg['message']['decrypted_message']['media']['date'] = $msg['message']['date'];
                    $msg['message']['decrypted_message']['media']['ttl_seconds'] = $msg['message']['decrypted_message']['ttl'];
                }
                $msg['message']['decrypted_message']['out'] = true;
                $msg['message']['decrypted_message']['date'] = $msg['message']['date'];
                $msg['message']['decrypted_message']['chat_id'] = $msg['message']['chat_id'];
                $this->outgoing[$request['seq']] = $msg;
                EventLoop::queue($this->API->saveUpdate(...), $msg);
            }
            return $msg;
        } finally {
            EventLoop::queue($lock->release(...));
        }
    }
    private static function transformDecryptedUpdate(array &$update): void
    {
        $decryptedMessage = &$update['message']['decrypted_message'];

        $decryptedMessage['out'] = false;
        $decryptedMessage['date'] = $update['message']['date'];
        $decryptedMessage['chat_id'] = $update['message']['chat_id'];

        if ($decryptedMessage['_'] === 'decryptedMessage'
            && isset($decryptedMessage['media'])
            && $decryptedMessage['media']['_'] !== 'decryptedMessageMediaEmpty'
        ) {
            $decryptedMessage['media']['file'] = $update['message']['file'];
            $decryptedMessage['media']['date'] = $update['message']['date'];
            $decryptedMessage['media']['ttl_seconds'] = $decryptedMessage['ttl'];
        }
    }
    private function handleDecryptedUpdate(array $update): void
    {
        $decryptedMessage = $update['message']['decrypted_message'];
        if ($decryptedMessage['_'] === 'decryptedMessage') {
            $this->API->saveUpdate($update);
            return;
        }
        if ($decryptedMessage['_'] === 'decryptedMessageService') {
            $action = $decryptedMessage['action'];
            switch ($action['_']) {
                case 'decryptedMessageActionRequestKey':
                    $this->acceptRekey($action);
                    return;
                case 'decryptedMessageActionAcceptKey':
                    $this->commitRekey($action);
                    return;
                case 'decryptedMessageActionCommitKey':
                    $this->completeRekey($action);
                    return;
                case 'decryptedMessageActionNotifyLayer':
                    if ($action['layer'] > $this->remoteLayer) {
                        $this->API->logger("Applying layer {$action['layer']} notification in $this");
                        $this->remoteLayer = $action['layer'];
                        if ($action['layer'] >= 46 && time() - $this->public->created > 15) {
                            $this->notifyLayer();
                        }
                        if ($action['layer'] >= 73) {
                            $this->mtproto = 2;
                        }
                    } else {
                        $this->API->logger("Ignoring layer {$action['layer']} notification in $this");
                    }
                    return;
                case 'decryptedMessageActionSetMessageTTL':
                    $this->ttl = $action['ttl_seconds'];
                    $this->API->saveUpdate($update);
                    return;
                case 'decryptedMessageActionNoop':
                    return;
                case 'decryptedMessageActionResend':
                    $this->handleResend($action);
                    return;
                default:
                    $this->API->saveUpdate($update);
            }
            return;
        }
        throw new ResponseException('Unrecognized decrypted message received: '.var_export($update, true));
    }
    private function handleResend(array &$action): void
    {
        if (isset($action['handled'])) {
            return;
        }
        $this->API->logger("Resending messages for $this: ".json_encode($action), Logger::WARNING);
        $action['start_seq_no'] -= $this->out_seq_no_base;
        $action['end_seq_no'] -= $this->out_seq_no_base;
        $action['start_seq_no'] >>= 1;
        $action['end_seq_no'] >>= 1;
        $action['handled'] = true;
        $this->API->logger("Resending messages for $this (after): ".json_encode($action), Logger::WARNING);
        for ($seq = $action['start_seq_no']; $seq <= $action['end_seq_no']; $seq++) {
            $msg = $this->outgoing[$seq];
            $this->API->methodCallAsyncRead($msg['method'], $msg[$seq]);
        }
    }
    /**
     * Handle encrypted update.
     *
     * @internal
     */
    public function handleEncryptedUpdate(array $message): bool
    {
        $message['message']['bytes'] = (string) $message['message']['bytes'];
        $auth_key_id = substr($message['message']['bytes'], 0, 8);
        $old = false;
        if ($auth_key_id !== $this->key['fingerprint']) {
            if (isset($this->oldKey['fingerprint'])) {
                if ($auth_key_id !== $this->oldKey['fingerprint']) {
                    $this->discard();
                    throw new SecurityException('Key fingerprint mismatch');
                }
                $old = true;
            } else {
                $this->discard();
                throw new SecurityException('Key fingerprint mismatch');
            }
        }
        $message_key = substr($message['message']['bytes'], 8, 16);
        $encrypted_data = substr($message['message']['bytes'], 24);
        if ($this->mtproto === 2) {
            $this->API->logger('Trying MTProto v2 decryption for '.$this.'...', Logger::NOTICE);
            try {
                $message_data = $this->tryMTProtoV2Decrypt($message_key, $old, $encrypted_data);
                $this->API->logger('MTProto v2 decryption OK for '.$this.'...', Logger::NOTICE);
            } catch (SecurityException $e) {
                if ($this->remoteLayer >= 73) {
                    // && !$this->waitingGaps
                    throw $e;
                }
                $this->API->logger('MTProto v2 decryption failed with message '.$e->getMessage().', trying MTProto v1 decryption for '.$this.'...', Logger::NOTICE);
                $message_data = $this->tryMTProtoV1Decrypt($message_key, $old, $encrypted_data);
                $this->API->logger('MTProto v1 decryption OK for '.$this.'...', Logger::NOTICE);
                $this->mtproto = 1;
            }
        } else {
            $this->API->logger('Trying MTProto v1 decryption for '.$this.'...', Logger::NOTICE);
            try {
                $message_data = $this->tryMTProtoV1Decrypt($message_key, $old, $encrypted_data);
                $this->API->logger('MTProto v1 decryption OK for '.$this.'...', Logger::NOTICE);
            } catch (SecurityException $e) {
                $this->API->logger('MTProto v1 decryption failed with message '.$e->getMessage().', trying MTProto v2 decryption for '.$this.'...', Logger::NOTICE);
                $message_data = $this->tryMTProtoV2Decrypt($message_key, $old, $encrypted_data);
                $this->API->logger('MTProto v2 decryption OK for '.$this.'...', Logger::NOTICE);
                $this->mtproto = 2;
            }
        }
        $deserialized = $this->API->getTL()->deserialize($message_data, ['type' => '']);
        $this->ttr--;
        if (($this->ttr <= 0 || time() - $this->updated > 7 * 24 * 60 * 60) && $this->rekeyState === RekeyState::IDLE) {
            $this->rekey();
        }
        unset($message['message']['bytes']);
        $message['message']['decrypted_message'] = $deserialized;

        if ($message['message']['decrypted_message']['_'] === 'decryptedMessageLayer') {
            foreach ($this->checkSecretOutSeqNo($message) as $message) {
                $this->checkSecretInSeqNo(
                    $message['message']['decrypted_message']['in_seq_no']
                );
                $layer = $message['message']['decrypted_message']['layer'];
                if ($layer >= 46 && $layer > $this->remoteLayer) {
                    $this->remoteLayer = $layer;
                    if (time() - $this->public->created > 15) {
                        $this->notifyLayer();
                    }
                }
                $message['message']['decrypted_message'] = $message['message']['decrypted_message']['message'];
                self::transformDecryptedUpdate($message);
                $this->incoming[$seq = $this->in_seq_no++] = $message;
                $this->randomIdMap[$message['message']['decrypted_message']['random_id']] = [$seq, false];
                $this->handleDecryptedUpdate($message);
            }
        } else {
            self::transformDecryptedUpdate($message);
            $this->handleDecryptedUpdate($message);
        }
        return true;
    }

    private function tryMTProtoV1Decrypt(string $message_key, bool $old, string $encrypted_data): string
    {
        $key = $old ? $this->oldKey : $this->key;
        \assert($key !== null);
        [$aes_key, $aes_iv] = Crypt::oldKdf($message_key, $key['auth_key'], true);
        $decrypted_data = Crypt::igeDecrypt($encrypted_data, $aes_key, $aes_iv);
        $message_data_length = unpack('V', substr($decrypted_data, 0, 4))[1];
        $message_data = substr($decrypted_data, 4, $message_data_length);
        if ($message_data_length > \strlen($decrypted_data)) {
            throw new SecurityException('message_data_length is too big');
        }
        if ($message_key != substr(sha1(substr($decrypted_data, 0, 4 + $message_data_length), true), -16)) {
            throw new SecurityException('Msg_key mismatch');
        }
        if (\strlen($decrypted_data) - 4 - $message_data_length > 15) {
            throw new SecurityException('difference between message_data_length and the length of the remaining decrypted buffer is too big');
        }
        if (\strlen($decrypted_data) % 16 != 0) {
            throw new SecurityException("Length of decrypted data is not divisible by 16");
        }
        return $message_data;
    }

    private function tryMTProtoV2Decrypt(string $message_key, bool $old, string $encrypted_data): string
    {
        $key = $old ? $this->oldKey : $this->key;
        \assert($key !== null);
        $key = $key['auth_key'];
        [$aes_key, $aes_iv] = Crypt::kdf($message_key, $key, !$this->public->creator);
        $decrypted_data = Crypt::igeDecrypt($encrypted_data, $aes_key, $aes_iv);
        if ($message_key != substr(hash('sha256', substr($key, 88 + ($this->public->creator ? 8 : 0), 32).$decrypted_data, true), 8, 16)) {
            throw new SecurityException('Msg_key mismatch');
        }
        $message_data_length = unpack('V', substr($decrypted_data, 0, 4))[1];
        $message_data = substr($decrypted_data, 4, $message_data_length);
        if ($message_data_length > \strlen($decrypted_data)) {
            throw new SecurityException('message_data_length is too big');
        }
        if (\strlen($decrypted_data) - 4 - $message_data_length < 12) {
            throw new SecurityException('padding is too small');
        }
        if (\strlen($decrypted_data) - 4 - $message_data_length > 1024) {
            throw new SecurityException('padding is too big');
        }
        if (\strlen($decrypted_data) % 16 != 0) {
            throw new SecurityException("Length of decrypted data is not divisible by 16");
        }
        return $message_data;
    }

    private function checkSecretInSeqNo(int $seqno): void
    {
        $seqno = ($seqno - $this->out_seq_no_base) >> 1;
        if ($seqno < $this->remote_in_seq_no) {
            $this->API->logger("Discarding $this, in_seq_no is decreasing", Logger::LEVEL_FATAL);
            $this->discard();
            throw new SecurityException('in_seq_no is decreasing');
        }
        if ($seqno > $this->out_seq_no + 1) {
            $this->API->logger("Discarding $this, in_seq_no is too big", Logger::LEVEL_FATAL);
            $this->discard();
            throw new SecurityException('in_seq_no is too big');
        }
        $this->remote_in_seq_no = $seqno;
    }

    private ?int $gapEnd = null;
    private ?int $gapQueueSeq = null;
    private array $gapQueue = [];
    private array $gapQuery = [];

    private function checkSecretOutSeqNo(array $message): \Generator
    {
        $seqno = $message['message']['decrypted_message']['out_seq_no'];
        $seqno = ($seqno - $this->in_seq_no_base) >> 1;
        $C_plus_one = $this->in_seq_no;
        //$this->API->logger($C, $seqno);
        if ($seqno < $C_plus_one) {
            // <= C
            $this->API->logger("WARNING: dropping repeated message in $this with seqno $seqno");
            return;
        }
        if ($seqno > $C_plus_one) {
            // > C+1
            if ($message['message']['decrypted_message']['message']['_'] === 'decryptedMessageService'
                && $message['message']['decrypted_message']['message']['action']['_'] === 'decryptedMessageActionResend'
            ) {
                $this->handleResend($message['message']['decrypted_message']['message']['action']);
            }
            if ($this->gapEnd !== null) {
                // Already recovering gap...
                $C_plus_one_gap = $this->gapQueueSeq;
                if ($seqno < $C_plus_one_gap) {
                    // <= C
                    $this->API->logger("WARNING: dropping repeated message in $this with seqno $seqno while recovering gaps");
                    return;
                }
                if ($seqno > $C_plus_one_gap) {
                    // > C+1
                    $this->API->logger("Discarding $this because out_seq_no gap detected: ($seqno > $C_plus_one_gap), but already recovering gap!", Logger::LEVEL_FATAL);
                    $this->discard();
                    throw new SecurityException("Additional out_seq_no gap detected!");
                }
                $this->API->logger("WARNING: queueing message $seqno in $this while recovering gaps");
                $this->gapQueue []= $message;
                $this->gapQueueSeq = $seqno+1;
                $this->API->methodCallAsyncRead('messages.sendEncryptedService', $this->gapQuery);
                return;
            }
            $this->API->logger("Requesting resending in $this, out_seq_no gap detected: ($seqno > $C_plus_one)", Logger::LEVEL_FATAL);
            $this->gapEnd = $seqno-1;
            $this->gapQueue = [$message];
            $this->gapQueueSeq = $seqno+1;
            $this->gapQuery = ['peer' => $this->id, 'message' => ['_' => 'decryptedMessageService', 'action' => [
                '_' => 'decryptedMessageActionResend',
                'start_seq_no' => $C_plus_one * 2 + $this->in_seq_no_base,
                'end_seq_no' => $this->gapEnd * 2 + $this->in_seq_no_base,
            ]]];
            $this->API->methodCallAsyncRead('messages.sendEncryptedService', $this->gapQuery);
            return;
        }
        yield $message;
        if ($seqno === $this->gapEnd) {
            $queue = $this->gapQueue;
            $this->gapQueue = [];
            $this->gapQueueSeq = null;
            $this->gapEnd = null;
            yield from $queue;
        }
    }
    private function generateSecretInSeqNo(): int
    {
        return $this->remoteLayer > 8 ? $this->in_seq_no * 2 + $this->in_seq_no_base : -1;
    }
    private function generateSecretOutSeqNo(): int
    {
        return $this->remoteLayer > 8 ? $this->out_seq_no * 2 + $this->out_seq_no_base : -1;
    }

    public function getMessage(int $random_id): array
    {
        $result = $this->randomIdMap[$random_id];
        if ($result === null) {
            throw new AssertionError("The secret message with ID $random_id does not exist!");
        }
        [$seq, $outgoing] = $result;
        return $outgoing ? $this->outgoing[$seq] : $this->incoming[$seq];
    }
    public function __toString(): string
    {
        return "secret chat {$this->id}";
    }
}
