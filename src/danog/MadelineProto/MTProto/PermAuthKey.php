<?php
/**
 * MTProto permanent auth key.
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

namespace danog\MadelineProto\MTProto;

/**
 * MTProto permanent auth key.
 */
class PermAuthKey extends AuthKey
{
    /**
     * Whether this auth key is authorized (as in associated to an account on Telegram).
     *
     * @var boolean
     */
    private $authorized = false;

    /**
     * Constructor function.
     *
     * @param array $old Old auth key array
     */
    public function __construct(array $old = [])
    {
        parent::__construct($old);
        if (isset($old['authorized'])) {
            $this->authorized($old['authorized']);
        }
    }
    /**
     * Check if we are logged in.
     *
     * @return boolean
     */
    public function isAuthorized(): bool
    {
        return $this->authorized;
    }

    /**
     * Set the authorized boolean.
     *
     * @param boolean $authorized Whether we are authorized
     *
     * @return void
     */
    public function authorized(bool $authorized)
    {
        $this->authorized = $authorized;
    }


    /**
     * JSON serialization function.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'auth_key' => 'pony'.\base64_encode($this->authKey),
            'server_salt' => $this->serverSalt,
            'authorized' => $this->authorized
        ];
    }
    /**
     * Sleep function.
     *
     * @return array
     */
    public function __sleep()
    {
        return [
            'authKey',
            'id',
            'serverSalt',
            'authorized'
        ];
    }
}
