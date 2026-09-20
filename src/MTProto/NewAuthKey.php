<?php

declare(strict_types=1);

/**
 * MTProto Auth key.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProto;

use danog\MadelineProto\API;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\Reactive\Publisher;
use danog\MadelineProto\Reactive\SimpleSubscriber;
use Webmozart\Assert\Assert;

/**
 * MTProto auth key.
 *
 * @internal
 *
 * @implements SimpleSubscriber<ConnectionState|LoginState>
 */
final class NewAuthKey implements SimpleSubscriber
{
    private ?string $authKey = null;
    private ?string $id = null;
    private ?string $tempAuthKey = null;
    private ?string $tempAuthKeyForHash = null;
    private ?string $tempId = null;
    public ?string $serverSalt = null;

    /** @var Publisher<ConnectionState> */
    public readonly Publisher $connectionState;

    private ?int $authedDcId;

    public function __construct(
        public readonly bool $isMedia,
        public readonly bool $isCdn,
        public readonly int $dcId,
        Publisher $loginState,
        private readonly ?self $mainKey = null
    ) {
        $this->connectionState = new Publisher(
            $isCdn
                ? ConnectionState::UNENCRYPTED
                : ($isMedia ? ConnectionState::UNENCRYPTED_MEDIA_WAITING_MAIN : ConnectionState::UNENCRYPTED_NO_PERMANENT)
        );
        if ($mainKey === null) {
            Assert::false($isMedia);
        } else {
            Assert::true($isMedia);
            $mainKey->connectionState->subscribe($this);
        }
        $loginState->subscribe($this);
    }

    #[\Override]
    public function onSimpleStateChange($state): void
    {
        if ($state instanceof ConnectionState) {
            $state = $this->mainKey->connectionState->getState();

            if ($this->connectionState->getState() === ConnectionState::UNENCRYPTED_MEDIA_WAITING_MAIN) {
                if ($state === ConnectionState::ENCRYPTED) {
                    $this->setAuthKey($this->mainKey->authKey);
                }
            } else {
                if ($state === ConnectionState::UNENCRYPTED_NO_PERMANENT) {
                    $this->setAuthKey(null);
                }
            }
            return;
        }

        Assert::isInstanceOf($state, LoginState::class);
        $this->authedDcId = $state->state === API::LOGGED_IN
            ? $state->authorizedDc
            : null;
        if ($this->connectionState->getState() === ConnectionState::ENCRYPTED_NOT_AUTHED
            || $this->connectionState->getState() === ConnectionState::ENCRYPTED_NOT_AUTHED_NO_LOGIN
        ) {
            if ($this->authedDcId !== null) {
                $state = $this->dcId === $this->authedDcId
                    ? ConnectionState::ENCRYPTED
                    : ConnectionState::ENCRYPTED_NOT_AUTHED;
            } else {
                $state = ConnectionState::ENCRYPTED_NOT_AUTHED_NO_LOGIN;
            }
            $this->connectionState->publish($state);
        } elseif ($this->authedDcId === null && $this->connectionState->getState() === ConnectionState::ENCRYPTED) {
            $this->setTempAuthKey(null, null);
        }
    }

    public function setAuthKey(?string $authKey): void
    {
        $this->authKey = $authKey;
        if ($authKey === null) {
            $this->id = null;
        } else {
            $this->id = substr(sha1($authKey, true), -8);
        }
        $this->setTempAuthKey(null, null);
    }
    public function setTempAuthKey(?string $authKey, ?string $serverSalt): void
    {
        $this->tempAuthKey = $authKey;
        $this->serverSalt = $serverSalt;
        if ($authKey === null) {
            Assert::null($serverSalt, 'Server salt must be null if auth key is null');
            $this->tempId = null;
            $this->tempAuthKeyForHash = null;

            $this->connectionState->publish(
                $this->isCdn || $this->id !== null
                    ? ConnectionState::UNENCRYPTED
                    : ($this->isMedia ? ConnectionState::UNENCRYPTED_MEDIA_WAITING_MAIN : ConnectionState::UNENCRYPTED_NO_PERMANENT)
            );
        } else {
            Assert::notNull($serverSalt, 'Server salt must not be null if auth key is not null');
            if (!$this->isCdn) {
                Assert::notNull($this->id, 'Auth key must not be null if temp auth key is not null');
            }
            $this->tempId = substr(sha1($authKey, true), -8);
            $this->tempAuthKeyForHash = substr($authKey, 88, 32);
            $this->connectionState->publish(
                $this->isCdn
                ? ConnectionState::ENCRYPTED_NOT_INITED
                : ConnectionState::ENCRYPTED_NOT_BOUND
            );
        }
    }
    /**
     * @return list{string, string}
     *
     * @psalm-mutation-free
     */
    public function pfsKdf(string $message_key): array
    {
        return Crypt::oldKdf($message_key, $this->authKey);
    }
    /**
     * Get auth key ID.
     */
    public function getID(): ?string
    {
        return $this->id;
    }
    /**
     * Get auth key.
     */
    public function getAuthKey(): ?string
    {
        return $this->authKey;
    }
    /**
     * Get temp auth key.
     */
    public function getTempAuthKey(): ?string
    {
        return $this->tempAuthKey;
    }
    /**
     * Get auth key.
     */
    public function getTempAuthKeyForHash(): ?string
    {
        return $this->tempAuthKeyForHash;
    }
    /**
     * Get auth key ID.
     */
    public function getTempID(): ?string
    {
        return $this->tempId;
    }

    /**
     * Get server salt.
     */
    public function getServerSalt(): ?string
    {
        return $this->serverSalt;
    }
    /**
     * Get server salt.
     *
     * @psalm-external-mutation-free
     */
    public function setServerSalt(?string $salt): void
    {
        $this->serverSalt = $salt;
    }

    public function bind(): void
    {
        Assert::eq($this->connectionState->getState(), ConnectionState::ENCRYPTED_NOT_BOUND);
        $state = ConnectionState::ENCRYPTED_NOT_INITED;
        $this->connectionState->publish($state);
    }
    public function init(): void
    {
        Assert::eq($this->connectionState->getState(), ConnectionState::ENCRYPTED_NOT_INITED);
        $state = $this->isCdn
            ? ConnectionState::ENCRYPTED
            : (
                $this->authedDcId === null
                ? ConnectionState::ENCRYPTED_NOT_AUTHED_NO_LOGIN
                : (
                    $this->authedDcId === $this->dcId || $this->isMedia
                        ? ConnectionState::ENCRYPTED
                        : ConnectionState::ENCRYPTED_NOT_AUTHED
                )
            );
        $this->connectionState->publish($state);
    }
    public function authorize(): void
    {
        Assert::eq($this->connectionState->getState(), ConnectionState::ENCRYPTED_NOT_AUTHED);
        $state = ConnectionState::ENCRYPTED;
        $this->connectionState->publish($state);
    }
}
