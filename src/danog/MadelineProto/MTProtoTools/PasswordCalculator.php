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
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

use danog\MadelineProto\Exception;
use danog\MadelineProto\Tools;
use phpseclib\Math\BigInteger;

/**
 * Manages password calculation
 */
class PasswordCalculator
{
    use AuthKeyHandler;
    use Tools;
    private $new_algo;
    private $secure_random = '';

    private $current_algo;
    private $srp_B;
    private $srp_id;
    
    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function addInfo(array $object)
    {
        if ($object['_'] !== 'account.password') {
            throw new Exception('Wrong constructor');
        }
        if ($object['has_secure_values']) {
            throw new Exception('Cannot parse secure values');
        }
        if ($object['has_password']) {
            switch ($object['current_algo']['_']) {
                case 'passwordKdfAlgoUnknown':
                    throw new Exception('Update your client to continue');

                case 'passwordKdfAlgoSHA256SHA256PBKDF2HMACSHA512iter100000SHA256ModPow':
                    $object['current_algo']['g'] = new BigInteger((string) $object['current_algo']['g']);
                    $object['current_algo']['p'] = new BigInteger((string) $object['current_algo']['p'], 256);
                    $this->check_p_g($object['current_algo']['p'], $object['current_algo']['g']);
                    $object['current_algo']['g_padded'] = str_pad($object['current_algo']['g']->toBytes(), 256, chr(0), \STR_PAD_LEFT);
                    $object['current_algo']['p_padded'] = str_pad($object['current_algo']['p']->toBytes(), 256, chr(0), \STR_PAD_LEFT);

                    break;
                default:
                    throw new Exception("Unknown KDF algo {$object['current_algo']['_']}");
            }
            $this->current_algo = $object['current_algo'];
            $this->srp_B = new BigInteger((string) $object['srp_B'], 256);
            $this->srp_id = $object['srp_id'];
        } else {
            $this->current_algo = null;
            $this->srp_B = null;
            $this->srp_id = null;
        }
        switch ($object['new_algo']['_']) {
            case 'passwordKdfAlgoUnknown':
                throw new Exception('Update your client to continue');

            case 'passwordKdfAlgoSHA256SHA256PBKDF2HMACSHA512iter100000SHA256ModPow':
                $object['new_algo']['g'] = new BigInteger((string) $object['new_algo']['g']);
                $object['new_algo']['p'] = new BigInteger((string) $object['new_algo']['p'], 256);
                $this->check_p_g($object['new_algo']['p'], $object['new_algo']['g']);
                $object['new_algo']['g_padded'] = str_pad($object['new_algo']['g']->toBytes(), 256, chr(0), \STR_PAD_LEFT);
                $object['new_algo']['p_padded'] = str_pad($object['new_algo']['p']->toBytes(), 256, chr(0), \STR_PAD_LEFT);

                break;
            default:
                throw new Exception("Unknown KDF algo {$object['new_algo']['_']}");
        }
        $this->new_algo = $object['new_algo'];
        $this->secure_random = $object['secure_random'];
    }
    public function createSalt(string $prefix = ''): string
    {
        return $prefix . $this->random(32);
    }
    public function hashSha256(string $data, string $salt): string
    {
        return hash('sha256', $salt . $data . $salt, true);
    }
    public function hashPassword(string $password, string $client_salt, string $server_salt): string
    {
        $buf = $this->hashSha256($password, $client_salt);
        $buf = $this->hashSha256($password, $server_salt);
        $hash = hash_pbkdf2('sha512', $buf, $client_salt, 100000, 0, true);
        return $this->hashSha256($hash, $server_salt);
    }
    public function getCheckPassword(string $password): array
    {
        if ($password === '') {
            return ['_' => 'inputCheckPasswordEmpty'];
        }
        $client_salt = $this->current_algo['salt1'];
        $server_salt = $this->current_algo['salt2'];
        $g = $this->current_algo['g'];
        $g_padded = $this->current_algo['g_padded'];
        $p = $this->current_algo['p'];
        $p_padded = $this->current_algo['p_padded'];
        $B = $this->srp_B;
        $id = $this->srp_id;
        $x = new BigInteger($this->hashPassword($password, $client_salt, $server_salt), 256);
        $a = new BigInteger($this->random(2048 / 8), 256);
        $A = $g->powMod($a, $p);
        $u = new BigInteger(hash('sha256', $A . $B, true), 256);
        $k = new BigInteger(hash('sha256', $p . $g_padded, true), 256);
        $v = $g->powMod($x, $p);
        $kv = $k->powMod($v, $p);
        $t = $B->subtract($kv);

        if ($t->compare(\danog\MadelineProto\Magic::$zero) < 0) {
            $t = $t->add($p);
        }
        $exp = $u->multiply($x);
        $exp = $exp->add($a);
        $S = $t->modExp($exp, $p);
        $S_padded = str_pad($S->toBytes(), 256, chr(0), \STR_PAD_LEFT);
        $K = hash('sha256', $S_padded, true);
        $h1 = hash('sha256', $p_padded, true);
        $h2 = hash('sha256', $g_padded, true);
        $h1 ^= $h2;
        $M = hash('sha256', $h1 . hash('sha256', $client_salt, true) . hash('sha256', $server_salt, true) . $A . $B . $K, true);

        return ['_' => 'inputCheckPasswordSRP', 'srp_id' => $id, 'A' => $A, 'M1' => $M];
    }
    public function getPassword(array $params): array
    {
        $return = ['password' => $this->getCheckPassword(isset($params['old_password']) ? $params['old_password'] : ''), 'new_settings' => ['_' => 'account.passwordInputSettings']];
        $new_settings = &$return['new_settings'];

        if (isset($params['password'])) {
            $client_salt = $this->createSalt($this->new_algo['salt1']);
            $server_salt = $this->new_algo['salt2'];
            $g = $this->new_algo['g'];
            $g_padded = $this->new_algo['g_padded'];
            $p = $this->new_algo['p'];
            $p_padded = $this->new_algo['p_padded'];
            $x = new BigInteger($this->hashPassword($params['password'], $client_salt, $server_salt), 256);
            $v = $g->powMod($x, $p);
            $v_padded = str_pad($v->toBytes(), 256, chr(0), \STR_PAD_LEFT);

            $new_settings['new_algo'] = [
                '_' => 'passwordKdfAlgoSHA256SHA256PBKDF2HMACSHA512iter100000SHA256ModPow',
                'salt1' => $client_salt,
                'salt2' => $server_salt,
                'g' => $g_padded,
                'p' => $p_padded,
            ];
            $new_settings['new_password_hash'] = $v_padded;
            $new_settings['hint'] = $params['hint'];
            if (isset($params['email'])) {
                $new_settings['email'] = $params['email'];
            }
        }
        return $new_settings;
    }
}
