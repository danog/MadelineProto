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

use Amp\Sync\LocalMutex;
use danog\MadelineProto\Db\DbArray;
use danog\MadelineProto\Db\DbPropertiesTrait;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\Secret\SecretFeedLoop;
use danog\MadelineProto\Loop\Update\UpdateLoop;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\MTProtoTools\DialogId;
use danog\MadelineProto\ResponseException;
use danog\MadelineProto\SecurityException;
use danog\MadelineProto\Tools;
use phpseclib3\Math\BigInteger;
use Revolt\EventLoop;
use Stringable;

/**
 * Represents a secret chat.
 * @internal
 *
 * @psalm-type TKey=array{auth_key: string, fingerprint: string, visualization_orig: string, visualization_46: string}
 */
final class SecretChatController implements Stringable
{
    use DbPropertiesTrait;

    protected function getDbPrefix(): string
    {
        return $this->API->getDbPrefix().'_'.$this->id;
    }

    /**
     * List of properties stored in database (memory or external).
     *
     * @see DbPropertiesFactory
     */
    protected static array $dbProperties = [
        'incoming' => ['innerMadelineProto' => true],
        'outgoing' => ['innerMadelineProto' => true],
    ];

    /**
     * @var DbArray<int, array>
     */
    private DbArray $incoming;
    /**
     * @var DbArray<int, array>
     */
    private DbArray $outgoing;
    private int $in_seq_no = 0;
    private int $out_seq_no = 0;
    private int $layer = 8;
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

    private int $in_seq_no_x;
    private int $out_seq_no_x;

    private int $ttl = 0;

    private SecretFeedLoop $feedLoop;
    public readonly SecretChat $public;
    public function __construct(
        private readonly MTProto $API,
        /** @var TKey */
        private array $key,
        public readonly int $id,
        public readonly int $accessHash,
        bool $creator,
        int $otherID,
    ) {
        if ($creator) {
            $this->in_seq_no_x = 1;
            $this->out_seq_no_x = 0;
        } else {
            $this->in_seq_no_x = 0;
            $this->out_seq_no_x = 1;
        }
        $this->public = new SecretChat(
            DialogId::fromSecretChatId($id),
            $creator,
            $otherID,
        );
        $this->updated = $this->public->created;
        $this->feedLoop = new SecretFeedLoop($API, $this);
        $this->feedLoop->start();
        $this->rekeyMutex = new LocalMutex;
        $this->encryptMutex = new LocalMutex;
        $this->init();
    }

    public function init(): void
    {
        $this->initDb($this->API);
    }
    
    public function __serialize(): array
    {
        $vars = \get_object_vars($this);
        unset($vars['rekeyMutex'], $vars['encryptMutex']);

        return $vars;
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
        $this->rekeyMutex = new LocalMutex;
        $this->encryptMutex = new LocalMutex;
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
            $this->API->logger->logger('Rekeying secret chat '.$this.'...', Logger::VERBOSE);
            $this->API->logger->logger('Generating a...', Logger::VERBOSE);
            $a = new BigInteger(Tools::random(256), 256);
            $this->API->logger->logger('Generating g_a...', Logger::VERBOSE);
            $g_a = $dh_config['g']->powMod($a, $dh_config['p']);
            Crypt::checkG($g_a, $dh_config['p']);
            $this->rekeyState = RekeyState::REQUESTED;
            $this->rekeyExchangeId = Tools::randomInt();
            $this->rekeyParam = $a;
            $this->API->methodCallAsyncRead('messages.sendEncryptedService', ['peer' => $this->id, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionRequestKey', 'g_a' => $g_a->toBytes(), 'exchange_id' => $e]]]);
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
            $this->API->logger->logger('Accepting rekeying of '.$this.'...', Logger::VERBOSE);
            $dh_config = $this->API->getDhConfig();
            $this->API->logger->logger('Generating b...', Logger::VERBOSE);
            $b = new BigInteger(Tools::random(256), 256);
            $params['g_a'] = new BigInteger((string) $params['g_a'], 256);
            Crypt::checkG($params['g_a'], $dh_config['p']);
            $key = ['auth_key' => \str_pad($params['g_a']->powMod($b, $dh_config['p'])->toBytes(), 256, \chr(0), STR_PAD_LEFT)];
            $key['fingerprint'] = \substr(\sha1($key['auth_key'], true), -8);
            $key['visualization_orig'] = $this->key['visualization_orig'];
            $key['visualization_46'] = \substr(\hash('sha256', $key['auth_key'], true), 20);

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
            $this->API->logger->logger('Committing rekeying of '.$this.'...', Logger::VERBOSE);
            $dh_config = ($this->API->getDhConfig());
            $params['g_b'] = new BigInteger((string) $params['g_b'], 256);
            Crypt::checkG($params['g_b'], $dh_config['p']);
            $key = ['auth_key' => \str_pad($params['g_b']->powMod($this->rekeyParam, $dh_config['p'])->toBytes(), 256, \chr(0), STR_PAD_LEFT)];
            $key['fingerprint'] = \substr(\sha1($key['auth_key'], true), -8);
            $key['visualization_orig'] = $this->key['visualization_orig'];
            $key['visualization_46'] = \substr(\hash('sha256', $key['auth_key'], true), 20);
            if ($key['fingerprint'] !== $params['key_fingerprint']) {
                $this->API->methodCallAsyncRead('messages.sendEncryptedService', ['peer' => $this->id, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionAbortKey', 'exchange_id' => $params['exchange_id']]]]);
                throw new SecurityException('Invalid key fingerprint!');
            }
            $this->API->methodCallAsyncRead('messages.sendEncryptedService', ['peer' => $this->id, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionCommitKey', 'exchange_id' => $params['exchange_id'], 'key_fingerprint' => $key['fingerprint']]]]);
            $this->rekeyState = RekeyState::IDLE;
            $this->oldKey = $this->key;
            $this->key = $key;
            $this->ttr = 100;
            $this->updated = \time();
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
            if ($this->rekeyKey['fingerprint'] !== $params['key_fingerprint']) {
                $this->API->methodCallAsyncRead('messages.sendEncryptedService', ['peer' => $this->id, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionAbortKey', 'exchange_id' => $params['exchange_id']]]]);
                throw new SecurityException('Invalid key fingerprint!');
            }
            $this->API->logger->logger('Completing rekeying of secret chat '.$this.'...', Logger::VERBOSE);
            $this->rekeyState = RekeyState::IDLE;
            $this->oldKey = $this->key;
            $this->key = $this->rekeyKey;
            $this->ttr = 100;
            $this->updated = \time();
            $this->API->methodCallAsyncRead('messages.sendEncryptedService', ['peer' => $this->id, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionNoop']]]);
            $this->API->logger->logger('Secret chat '.$this.' rekeyed successfully!', Logger::VERBOSE);
        } finally {
            EventLoop::queue($lock->release(...));
        }
    }

    private LocalMutex $encryptMutex;
    /**
     * Encrypt secret chat message.
     * @internal
     */
    public function encryptSecretMessage(MTProtoOutgoingMessage $msg): array
    {
        $body = $msg->getBody();
        if (isset($body['data'])) {
            return $body;
        }

        $lock = $this->encryptMutex->acquire();
        try {
            $this->ttr--;
            if ($this->layer > 8
                && ($this->ttr <= 0 || \time() - $this->updated > 7 * 24 * 60 * 60)
                && $this->rekeyState === RekeyState::IDLE
            ) {
                EventLoop::queue($this->rekey(...));
            }

            $body['data'] = $this->encryptSecretMessageInner($body['message']);
            unset($body['message']);

            $msg->getResultPromise()->finally($lock->release(...));
            return $body;
        } catch (\Throwable $e) {
            $lock->release();
            throw $e;
        }
    }
    private function encryptSecretMessageInner(array $message): void
    {
        $message['random_id'] = Tools::random(8);
        if ($this->layer > 8) {
            $message = ['_' => 'decryptedMessageLayer', 'layer' => $this->layer, 'in_seq_no' => $this->generateSecretInSeqNo(), 'out_seq_no' => $this->generateSecretOutSeqNo(), 'message' => $message];
            $this->out_seq_no++;
        }
        $this->outgoing[$this->out_seq_no] = $message;
        $constructor = $this->layer === 8 ? 'DecryptedMessage' : 'DecryptedMessageLayer';
        $message = $this->API->getTL()->serializeObject(['type' => $constructor], $message, $constructor, $this->layer);
        $message = Tools::packUnsignedInt(\strlen($message)).$message;
        if ($this->mtproto === 2) {
            $padding = Tools::posmod(-\strlen($message), 16);
            if ($padding < 12) {
                $padding += 16;
            }
            $message .= Tools::random($padding);
            $message_key = \substr(\hash('sha256', \substr($this->key['auth_key'], 88 + ($this->public->creator ? 0 : 8), 32).$message, true), 8, 16);
            [$aes_key, $aes_iv] = Crypt::kdf($message_key, $this->key['auth_key'], $this->public->creator);
        } else {
            $message_key = \substr(\sha1($message, true), -16);
            [$aes_key, $aes_iv] = Crypt::oldKdf($message_key, $this->key['auth_key'], true);
            $message .= Tools::random(Tools::posmod(-\strlen($message), 16));
        }
        $message = $this->key['fingerprint'].$message_key.Crypt::igeEncrypt($message, $aes_key, $aes_iv);
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
                    $this->layer = $action['layer'];
                    if ($action['layer'] >= 17 && \time() - $this->public->created > 15) {
                        $this->notifyLayer();
                    }
                    if ($action['layer'] >= 73) {
                        $this->mtproto = 2;
                    }
                    return;
                case 'decryptedMessageActionSetMessageTTL':
                    $this->ttl = $action['ttl_seconds'];
                    $this->API->saveUpdate($update);
                    return;
                case 'decryptedMessageActionNoop':
                    return;
                case 'decryptedMessageActionResend':
                    $action['start_seq_no'] -= $this->out_seq_no_x;
                    $action['end_seq_no'] -= $this->out_seq_no_x;
                    $action['start_seq_no'] /= 2;
                    $action['end_seq_no'] /= 2;
                    $this->API->logger->logger('Resending messages for '.$this, Logger::WARNING);
                    for ($seq = $action['start_seq_no']; $seq <= $action['end_seq_no']; $seq++) {
                        $this->API->methodCallAsyncRead('messages.sendEncrypted', [
                            'peer' => $this->id,
                            'message' => $this->outgoing[$seq]
                        ]);
                    }
                    return;
                default:
                    $this->API->saveUpdate($update);
            }
            return;
        }
        if ($decryptedMessage['_'] === 'decryptedMessageLayer') {
            if (($this->checkSecretOutSeqNo($decryptedMessage['out_seq_no']))
                && ($this->checkSecretInSeqNo($decryptedMessage['in_seq_no']))) {
                $this->in_seq_no++;
                if ($decryptedMessage['layer'] >= 17 && $decryptedMessage['layer'] !== $this->layer) {
                    $this->layer = $decryptedMessage['layer'];
                    if ($decryptedMessage['layer'] >= 17 && \time() - $this->public->created > 15) {
                        $this->notifyLayer();
                    }
                }
                $update['message']['decrypted_message'] = $decryptedMessage['message'];
                $this->handleDecryptedUpdate($update);
            }
            return;
        }
        throw new ResponseException('Unrecognized decrypted message received: '.\var_export($update, true));
    }
    /**
     * Handle encrypted update.
     *
     * @internal
     */
    public function handleEncryptedUpdate(array $message): bool
    {
        $message['message']['bytes'] = (string) $message['message']['bytes'];
        $auth_key_id = \substr($message['message']['bytes'], 0, 8);
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
        $message_key = \substr($message['message']['bytes'], 8, 16);
        $encrypted_data = \substr($message['message']['bytes'], 24);
        if ($this->mtproto === 2) {
            $this->API->logger->logger('Trying MTProto v2 decryption for '.$this.'...', Logger::NOTICE);
            try {
                $message_data = $this->tryMTProtoV2Decrypt($message_key, $old, $encrypted_data);
                $this->API->logger->logger('MTProto v2 decryption OK for '.$this.'...', Logger::NOTICE);
            } catch (SecurityException $e) {
                $this->API->logger->logger('MTProto v2 decryption failed with message '.$e->getMessage().', trying MTProto v1 decryption for '.$this.'...', Logger::NOTICE);
                $message_data = $this->tryMTProtoV1Decrypt($message_key, $old, $encrypted_data);
                $this->API->logger->logger('MTProto v1 decryption OK for '.$this.'...', Logger::NOTICE);
                $this->mtproto = 1;
            }
        } else {
            $this->API->logger->logger('Trying MTProto v1 decryption for '.$this.'...', Logger::NOTICE);
            try {
                $message_data = $this->tryMTProtoV1Decrypt($message_key, $old, $encrypted_data);
                $this->API->logger->logger('MTProto v1 decryption OK for '.$this.'...', Logger::NOTICE);
            } catch (SecurityException $e) {
                $this->API->logger->logger('MTProto v1 decryption failed with message '.$e->getMessage().', trying MTProto v2 decryption for '.$this.'...', Logger::NOTICE);
                $message_data = $this->tryMTProtoV2Decrypt($message_key, $old, $encrypted_data);
                $this->API->logger->logger('MTProto v2 decryption OK for '.$this.'...', Logger::NOTICE);
                $this->mtproto = 2;
            }
        }
        $deserialized = $this->API->getTL()->deserialize($message_data, ['type' => '']);
        $this->ttr--;
        if (($this->ttr <= 0 || \time() - $this->updated > 7 * 24 * 60 * 60) && $this->rekeyState === RekeyState::IDLE) {
            $this->rekey();
        }
        unset($message['message']['bytes']);
        $message['message']['decrypted_message'] = $deserialized;
        $this->incoming[$this->in_seq_no] = $message['message'];
        $this->handleDecryptedUpdate($message);
        return true;
    }

    private function tryMTProtoV1Decrypt(string $message_key, bool $old, string $encrypted_data): string
    {
        [$aes_key, $aes_iv] = Crypt::oldKdf($message_key, ($old ? $this->oldKey : $this->key)['auth_key'], true);
        $decrypted_data = Crypt::igeDecrypt($encrypted_data, $aes_key, $aes_iv);
        $message_data_length = \unpack('V', \substr($decrypted_data, 0, 4))[1];
        $message_data = \substr($decrypted_data, 4, $message_data_length);
        if ($message_data_length > \strlen($decrypted_data)) {
            throw new SecurityException('message_data_length is too big');
        }
        if ($message_key != \substr(\sha1(\substr($decrypted_data, 0, 4 + $message_data_length), true), -16)) {
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
        $key = ($old ? $this->oldKey : $this->key)['auth_key'];
        [$aes_key, $aes_iv] = Crypt::kdf($message_key, $key, !$this->public->creator);
        $decrypted_data = Crypt::igeDecrypt($encrypted_data, $aes_key, $aes_iv);
        if ($message_key != \substr(\hash('sha256', \substr($key, 88 + ($this->public->creator ? 8 : 0), 32).$decrypted_data, true), 8, 16)) {
            throw new SecurityException('Msg_key mismatch');
        }
        $message_data_length = \unpack('V', \substr($decrypted_data, 0, 4))[1];
        $message_data = \substr($decrypted_data, 4, $message_data_length);
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

    private function checkSecretInSeqNo(int $seqno): bool
    {
        $seqno = ($seqno - $this->out_seq_no_x) / 2;
        $last = 0;
        foreach ($this->incoming as $message) {
            if (isset($message['decrypted_message']['in_seq_no'])) {
                if (($message['decrypted_message']['in_seq_no'] - $this->out_seq_no_x) / 2 < $last) {
                    $this->API->logger->logger("Discarding $this, in_seq_no is not increasing", Logger::LEVEL_FATAL);
                    $this->discard();
                    throw new SecurityException('in_seq_no is not increasing');
                }
                $last = ($message['decrypted_message']['in_seq_no'] - $this->out_seq_no_x) / 2;
            }
        }
        if ($seqno > $this->out_seq_no + 1) {
            $this->API->logger->logger("Discarding $this, in_seq_no is too big", Logger::LEVEL_FATAL);
            $this->discard();
            throw new SecurityException('in_seq_no is too big');
        }
        return true;
    }
    private function checkSecretOutSeqNo(int $seqno): bool
    {
        $seqno = ($seqno - $this->in_seq_no_x) / 2;
        $C = 0;
        foreach ($this->incoming as $message) {
            if (isset($message['decrypted_message']['out_seq_no']) && $C < $this->in_seq_no) {
                $temp = ($message['decrypted_message']['out_seq_no'] - $this->in_seq_no_x) / 2;
                if ($temp !== $C) {
                    $this->API->logger->logger("Discarding $this, out_seq_no hole: should be $C, is $temp", Logger::LEVEL_FATAL);
                    $this->discard();
                    throw new SecurityException("out_seq_no hole: should be $C, is $temp");
                }
                $C++;
            }
        }
        //$this->API->logger->logger($C, $seqno);
        if ($seqno < $C) {
            // <= C
            $this->API->logger->logger('WARNING: dropping repeated message with seqno '.$seqno);
            return false;
        }
        if ($seqno > $C) {
            // > C+1
            $this->API->logger->logger("Discarding $this, out_seq_no gap detected: ($seqno > $C)", Logger::LEVEL_FATAL);
            $this->discard();
            throw new SecurityException('WARNING: out_seq_no gap detected ('.$seqno.' > '.$C.')!');
        }
        return true;
    }
    private function generateSecretInSeqNo(): int
    {
        return $this->layer > 8 ? $this->in_seq_no * 2 + $this->in_seq_no_x : -1;
    }
    private function generateSecretOutSeqNo(): int
    {
        return $this->layer > 8 ? $this->out_seq_no * 2 + $this->out_seq_no_x : -1;
    }

    public function __toString(): string
    {
        return "secret chat {$this->id}";
    }
}
