<?php

declare(strict_types=1);

/**
 * MTProto temporary auth key.
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

namespace danog\MadelineProto\MTProto;

use JsonSerializable;

/**
 * MTProto temporary auth key.
 *
 * @internal
 */
final class TempAuthKey extends AuthKey implements JsonSerializable
{
    /**
     * Bound auth key instance.
     *
     */
    private ?PermAuthKey $bound = null;
    /**
     * Expiration date.
     *
     */
    private int $expires = 0;
    /**
     * Whether the connection is inited for this auth key.
     *
     */
    protected bool $inited = false;
    /**
     * Constructor function.
     *
     * @param array $old Old auth key array
     */
    public function __construct(array $old = [])
    {
        parent::__construct($old);
        if (isset($old['expires'])) {
            $this->expires($old['expires']);
        }
        if (isset($old['connection_inited']) && $old['connection_inited']) {
            $this->init($old['connection_inited']);
        }
    }
    /**
     * Init or deinit connection for auth key.
     *
     * @param boolean $init Init or deinit
     */
    public function init(bool $init = true): void
    {
        $this->inited = $init;
    }
    /**
     * Check if connection is inited for auth key.
     */
    public function isInited(): bool
    {
        return $this->inited;
    }
    /**
     * Bind auth key.
     *
     * @param PermAuthKey|null $bound Permanent auth key
     * @param bool             $pfs   Whether to bind using PFS
     */
    public function bind(?PermAuthKey $bound, bool $pfs = true): void
    {
        $this->bound = $bound;
        if (!$pfs) {
            foreach (['authKey', 'id', 'serverSalt'] as $key) {
                $this->{$key} =& $bound->{$key};
            }
        }
    }
    /**
     * Check if auth key is bound.
     */
    public function isBound(): bool
    {
        return $this->bound !== null;
    }
    /**
     * Check if we are logged in.
     */
    public function isAuthorized(): bool
    {
        return $this->bound ? $this->bound->isAuthorized() : false;
    }
    /**
     * Set the authorized boolean.
     *
     * @param boolean $authorized Whether we are authorized
     */
    public function authorized(bool $authorized): void
    {
        $this->bound->authorized($authorized);
    }
    /**
     * Set expiration date of temporary auth key.
     *
     * @param integer $expires Expiration date
     */
    public function expires(int $expires): void
    {
        $this->expires = $expires;
    }
    /**
     * Check if auth key has expired.
     */
    public function expired(): bool
    {
        return time() > $this->expires;
    }
    /**
     * JSON serialization function.
     */
    public function jsonSerialize(): array
    {
        return ['auth_key' => 'pony'.base64_encode($this->authKey), 'server_salt' => $this->serverSalt, 'bound' => $this->isBound(), 'expires' => $this->expires, 'connection_inited' => $this->inited];
    }
    /**
     * Sleep function.
     */
    public function __sleep(): array
    {
        return ['authKey', 'id', 'serverSalt', 'bound', 'expires', 'inited'];
    }
}
