<?php
/**
 * Password calculator module.
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

use danog\MadelineProto\Exception;
use danog\MadelineProto\Magic;
use danog\MadelineProto\SecurityException;
use danog\MadelineProto\Tools;
use tgseclib\Math\BigInteger;

/**
 * Manages SRP password calculation.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 * @link   https://docs.madelineproto.xyz MadelineProto documentation
 */
class PasswordCalculator
{
    use AuthKeyHandler;
    use Tools;

    /**
     * The algorithm to use for calculating the hash of new passwords (a PasswordKdfAlgo object).
     *
     * @var array
     */
    private $new_algo;
    /**
     * A secure random string that can be used to compute the password.
     *
     * @var string
     */
    private $secure_random = '';

    /**
     * The algorithm to use for calculatuing the hash of the current password (a PasswordKdfAlgo object).
     *
     * @var array
     */
    private $current_algo;

    /**
     * SRP b parameter.
     *
     * @var BigInteger
     */
    private $srp_B;
    /**
     * SRP b parameter for hashing.
     *
     * @var BigInteger
     */
    private $srp_BForHash;
    /**
     * SRP ID.
     *
     * @var [type]
     */
    private $srp_id;
    /**
     * Logger.
     *
     * @var \danog\MadelineProto\Logger
     */
    public $logger;

    /**
     * Initialize logger.
     *
     * @param \danog\MadelineProto\Logger $logger
     */
    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Popupate 2FA configuration.
     *
     * @param array $object 2FA configuration object obtained using account.getPassword
     *
     * @return void
     */
    public function addInfo(array $object): void
    {
        if ($object['_'] !== 'account.password') {
            throw new Exception('Wrong constructor');
        }
        if ($object['has_secure_values']) {
            //throw new Exception('Cannot parse secure values');
        }
        if ($object['has_password']) {
            switch ($object['current_algo']['_']) {
                case 'passwordKdfAlgoUnknown':
                    throw new Exception('Update your client to continue');
                case 'passwordKdfAlgoSHA256SHA256PBKDF2HMACSHA512iter100000SHA256ModPow':
                    $object['current_algo']['g'] = new BigInteger($object['current_algo']['g']);
                    $object['current_algo']['p'] = new BigInteger((string) $object['current_algo']['p'], 256);
                    $this->checkPG($object['current_algo']['p'], $object['current_algo']['g']);
                    $object['current_algo']['gForHash'] = \str_pad($object['current_algo']['g']->toBytes(), 256, \chr(0), \STR_PAD_LEFT);
                    $object['current_algo']['pForHash'] = \str_pad($object['current_algo']['p']->toBytes(), 256, \chr(0), \STR_PAD_LEFT);

                    break;
                default:
                    throw new Exception("Unknown KDF algo {$object['current_algo']['_']}");
            }
            $this->current_algo = $object['current_algo'];
            $object['srp_B'] = new BigInteger((string) $object['srp_B'], 256);
            if ($object['srp_B']->compare(\danog\MadelineProto\Magic::$zero) < 0) {
                throw new SecurityException('srp_B < 0');
            }
            if ($object['srp_B']->compare($object['current_algo']['p']) > 0) {
                throw new SecurityException('srp_B > p');
            }
            $this->srp_B = $object['srp_B'];
            $this->srp_BForHash = \str_pad($object['srp_B']->toBytes(), 256, \chr(0), \STR_PAD_LEFT);
            $this->srp_id = $object['srp_id'];
        } else {
            $this->current_algo = null;
            $this->srp_B = null;
            $this->srp_BForHash = null;
            $this->srp_id = null;
        }
        switch ($object['new_algo']['_']) {
            case 'passwordKdfAlgoUnknown':
                throw new Exception('Update your client to continue');
            case 'passwordKdfAlgoSHA256SHA256PBKDF2HMACSHA512iter100000SHA256ModPow':
                $object['new_algo']['g'] = new BigInteger($object['new_algo']['g']);
                $object['new_algo']['p'] = new BigInteger((string) $object['new_algo']['p'], 256);
                $this->checkPG($object['new_algo']['p'], $object['new_algo']['g']);
                $object['new_algo']['gForHash'] = \str_pad($object['new_algo']['g']->toBytes(), 256, \chr(0), \STR_PAD_LEFT);
                $object['new_algo']['pForHash'] = \str_pad($object['new_algo']['p']->toBytes(), 256, \chr(0), \STR_PAD_LEFT);

                break;
            default:
                throw new Exception("Unknown KDF algo {$object['new_algo']['_']}");
        }
        $this->new_algo = $object['new_algo'];
        $this->secure_random = $object['secure_random'];
    }

    /**
     * Create a random string (eventually prefixed by the specified string).
     *
     * @param string $prefix Prefix
     * @return string Salt
     */
    public function createSalt(string $prefix = ''): string
    {
        return $prefix.\danog\MadelineProto\Tools::random(32);
    }

    /**
     * Hash specified data using the salt with SHA256.
     *
     * The result will be the SHA256 hash of the salt concatenated with the data concatenated with the salt
     *
     * @param string $data Data to hash
     * @param string $salt Salt
     * @return string Hash
     */
    public function hashSha256(string $data, string $salt): string
    {
        return \hash('sha256', $salt.$data.$salt, true);
    }

    /**
     * Hashes the specified password.
     *
     * @param string $password Password
     * @param string $client_salt Client salt
     * @param string $server_salt Server salt
     * @return string Resulting hash
     */
    public function hashPassword(string $password, string $client_salt, string $server_salt): string
    {
        $buf = $this->hashSha256($password, $client_salt);
        $buf = $this->hashSha256($buf, $server_salt);
        $hash = \hash_pbkdf2('sha512', $buf, $client_salt, 100000, 0, true);

        return $this->hashSha256($hash, $server_salt);
    }

    /**
     * Get the InputCheckPassword object for checking the validity of a password using account.checkPassword.
     *
     * @param string $password The password
     * @return array InputCheckPassword object
     */
    public function getCheckPassword(string $password): array
    {
        if ($password === '' || !$this->current_algo) {
            return ['_' => 'inputCheckPasswordEmpty'];
        }
        $client_salt = $this->current_algo['salt1'];
        $server_salt = $this->current_algo['salt2'];
        $g = $this->current_algo['g'];
        $gForHash = $this->current_algo['gForHash'];
        $p = $this->current_algo['p'];
        $pForHash = $this->current_algo['pForHash'];

        $B = $this->srp_B;
        $BForHash = $this->srp_BForHash;
        $id = $this->srp_id;

        $x = new BigInteger($this->hashPassword($password, $client_salt, $server_salt), 256);
        $g_x = $g->powMod($x, $p);

        $k = new BigInteger(\hash('sha256', $pForHash.$gForHash, true), 256);
        $kg_x = $k->multiply($g_x)->powMod(Magic::$one, $p);

        $a = new BigInteger(\danog\MadelineProto\Tools::random(2048 / 8), 256);
        $A = $g->powMod($a, $p);
        $this->checkG($A, $p);
        $AForHash = \str_pad($A->toBytes(), 256, \chr(0), \STR_PAD_LEFT);

        $b_kg_x = $B->powMod(Magic::$one, $p)->subtract($kg_x);

        $u = new BigInteger(\hash('sha256', $AForHash.$BForHash, true), 256);
        $ux = $u->multiply($x);
        $a_ux = $a->add($ux);

        $S = $b_kg_x->powMod($a_ux, $p);

        $SForHash = \str_pad($S->toBytes(), 256, \chr(0), \STR_PAD_LEFT);
        $K = \hash('sha256', $SForHash, true);

        $h1 = \hash('sha256', $pForHash, true);
        $h2 = \hash('sha256', $gForHash, true);
        $h1 ^= $h2;

        $M1 = \hash('sha256', $h1.\hash('sha256', $client_salt, true).\hash('sha256', $server_salt, true).$AForHash.$BForHash.$K, true);

        return ['_' => 'inputCheckPasswordSRP', 'srp_id' => $id, 'A' => $AForHash, 'M1' => $M1];
    }

    /**
     * Get parameters to be passed to the account.updatePasswordSettings to update/set a 2FA password.
     *
     * The input params array can contain password, new_password, email and hint params.
     *
     * @param  array $params Input params
     * @return array account.updatePasswordSettings parameters
     */
    public function getPassword(array $params): array
    {
        $oldPassword = $this->getCheckPassword($params['password'] ?? '');

        $return = ['password' => $oldPassword, 'new_settings' => ['_' => 'account.passwordInputSettings', 'new_algo' => ['_' => 'passwordKdfAlgoUnknown'], 'new_password_hash' => '', 'hint' => '']];

        $new_settings = &$return['new_settings'];

        if (isset($params['new_password']) && $params['new_password'] !== '') {
            $client_salt = $this->createSalt($this->new_algo['salt1']);
            $server_salt = $this->new_algo['salt2'];
            $g = $this->new_algo['g'];
            $p = $this->new_algo['p'];
            $pForHash = $this->new_algo['pForHash'];

            $x = new BigInteger($this->hashPassword($params['new_password'], $client_salt, $server_salt), 256);
            $v = $g->powMod($x, $p);
            $vForHash = \str_pad($v->toBytes(), 256, \chr(0), \STR_PAD_LEFT);

            $new_settings['new_algo'] = [
                '_' => 'passwordKdfAlgoSHA256SHA256PBKDF2HMACSHA512iter100000SHA256ModPow',
                'salt1' => $client_salt,
                'salt2' => $server_salt,
                'g' => (int) $g->toString(),
                'p' => $pForHash,
            ];
            $new_settings['new_password_hash'] = $vForHash;
            $new_settings['hint'] = $params['hint'] ?? '';
            if (isset($params['email'])) {
                $new_settings['email'] = $params['email'];
            }
        }

        return $return;
    }
}
