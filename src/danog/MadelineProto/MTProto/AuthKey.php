<?php

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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProto;

use JsonSerializable;
use Webmozart\Assert\Assert;

/**
 * MTProto auth key.
 */
abstract class AuthKey implements JsonSerializable
{
    /**
     * Auth key.
     *
     * @var ?string
     */
    protected $authKey;
    /**
     * Auth key ID.
     *
     * @var ?string
     */
    protected $id;
    /**
     * Server salt.
     *
     * @var ?string
     */
    protected $serverSalt;
    /**
     * Constructor function.
     *
     * @param array $old Old auth key array
     */
    public function __construct(array $old = [])
    {
        if (isset($old['auth_key'])) {
            if (\strlen($old['auth_key']) !== 2048 / 8 && \strpos($old['authkey'], 'pony') === 0) {
                $old['auth_key'] = \base64_decode(\substr($old['auth_key'], 4));
            }
            $this->setAuthKey($old['auth_key']);
        }
        if (isset($old['server_salt'])) {
            $this->setServerSalt($old['server_salt']);
        }
    }
    /**
     * Set auth key.
     *
     * @param string $authKey Authorization key
     *
     */
    public function setAuthKey(string $authKey): void
    {
        $this->authKey = $authKey;
        $this->id = \substr(\sha1($authKey, true), -8);
    }
    /**
     * Check if auth key is present.
     *
     * @return boolean
     */
    public function hasAuthKey(): bool
    {
        return $this->authKey !== null && $this->serverSalt !== null;
    }
    /**
     * Get auth key.
     *
     */
    public function getAuthKey(): string
    {
        Assert::notNull($this->authKey);
        return $this->authKey;
    }
    /**
     * Get auth key ID.
     *
     */
    public function getID(): string
    {
        Assert::notNull($this->id);
        return $this->id;
    }
    /**
     * Set server salt.
     *
     * @param string $salt Server salt
     *
     */
    public function setServerSalt(string $salt): void
    {
        $this->serverSalt = $salt;
    }
    /**
     * Get server salt.
     *
     */
    public function getServerSalt(): string
    {
        Assert::notNull($this->serverSalt);
        return $this->serverSalt;
    }
    /**
     * Check if has server salt.
     *
     * @return boolean
     */
    public function hasServerSalt(): bool
    {
        return $this->serverSalt !== null;
    }
    /**
     * Check if we are logged in.
     *
     * @return boolean
     */
    abstract public function isAuthorized(): bool;
    /**
     * Set the authorized boolean.
     *
     * @param boolean $authorized Whether we are authorized
     *
     */
    abstract public function authorized(bool $authorized): void;
}
