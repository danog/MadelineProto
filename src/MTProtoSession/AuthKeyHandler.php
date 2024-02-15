<?php

declare(strict_types=1);

/**
 * AuthKeyHandler module.
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

namespace danog\MadelineProto\MTProtoSession;

use danog\MadelineProto\DataCenterConnection;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\PermAuthKey;
use danog\MadelineProto\MTProto\TempAuthKey;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\SecurityException;
use danog\MadelineProto\Tools;
use danog\PrimeModule;
use phpseclib3\Math\BigInteger;
use Throwable;

use const PHP_EOL;
use function hash;

use function time;

/**
 * Manages the creation of the authorization key.
 *
 * https://core.telegram.org/mtproto/auth_key
 * https://core.telegram.org/mtproto/samples-auth_key
 *
 * @property DataCenterConnection $shared
 * @property int                  $datacenter
 * @property MTProto              $API
 * @property Logger               $logger
 *
 * @internal
 */
trait AuthKeyHandler
{
    /**
     * Create authorization key.
     *
     * @return ($temp is false ? PermAuthKey : TempAuthKey)|null
     */
    public function createAuthKey(bool $temp): PermAuthKey|TempAuthKey|null
    {
        $expires_in = $temp ? MTProto::PFS_DURATION : -1;
        $cdn = $this->isCDN();
        $test = $this->API->settings->getConnection()->getTestMode();

        for ($retry_id_total = 1; $retry_id_total <= $this->API->settings->getAuth()->getMaxAuthTries(); $retry_id_total++) {
            try {
                $this->API->logger('Requesting pq...', Logger::VERBOSE);
                /**
                 * ***********************************************************************
                 * Make pq request, DH exchange initiation.
                 *
                 * @method req_pq_multi
                 * @param [
                 *         int128         $nonce                             : The value of nonce is selected randomly by the client (random number) and identifies the client within this communication
                 * ]
                 * @return ResPQ [
                 *               int128         $nonce                             : The value of nonce is selected randomly by the server
                 *               int128         $server_nonce                         : The value of server_nonce is selected randomly by the server
                 *               string         $pq                             : This is a representation of a natural number (in binary big endian format). This number is the product of two different odd prime numbers
                 *               Vector long    $server_public_key_fingerprints                : This is a list of public RSA key fingerprints
                 *               ]
                 */
                $nonce = Tools::random(16);
                $ResPQ = $this->methodCallAsyncRead('req_pq_multi', ['nonce' => $nonce]);
                /*
                 * ***********************************************************************
                 * Check if the client's nonce and the server's nonce are the same
                 */
                if ($ResPQ['nonce'] !== $nonce) {
                    throw new SecurityException('wrong nonce');
                }
                /*
                 * ***********************************************************************
                 * Find our key in the server_public_key_fingerprints vector
                 */
                foreach ($this->API->getRsaKeys($test, $cdn) as $curkey) {
                    if (\in_array($curkey->fp, $ResPQ['server_public_key_fingerprints'], true)) {
                        $key = $curkey;
                    }
                }
                if (!isset($key)) {
                    if ($cdn) {
                        $this->API->logger('Could not find required CDN public key, postponing CDN handshake...');
                        return null;
                    }
                    throw new SecurityException("Couldn't find any of our keys in the server_public_key_fingerprints vector.");
                }
                $pq_bytes = $ResPQ['pq'];
                $server_nonce = $ResPQ['server_nonce'];
                /*
                 * ***********************************************************************
                 * Compute p and q
                 */
                $ok = false;
                $pq = Tools::unpackSignedLong(strrev($pq_bytes));
                foreach ([
                    'native_single_cpp',
                    'python_single_alt',
                    'python_single',
                    'native_single',
                ] as $method) {
                    $this->API->logger("Factorizing with $method (please wait, might take a while)");
                    if ($method !== 'native_single_cpp') {
                        $this->API->logger('Install https://prime.madelineproto.xyz and the FFI extension to speed this up!');
                    }

                    $p = 0;
                    $q = 0;
                    try {
                        $p = PrimeModule::$method($pq);
                    } catch (Throwable $e) {
                        $this->API->logger("While factorizing with $method: $e");
                    }

                    if ($p) {
                        $q = $pq / $p;
                        if ($p > $q) {
                            [$p, $q] = [$q, $p];
                        }
                        if ($pq === $p*$q) {
                            $ok = true;
                            break;
                        }
                    }
                }
                if (!$ok) {
                    throw new SecurityException("Couldn't compute p and q, install prime.madelineproto.xyz to fix. Original pq: {$pq}, computed p: {$p}, computed q: {$q}, computed pq: ".$p*$q);
                }
                $this->API->logger('Factorization '.$pq.' = '.$p.' * '.$q, Logger::VERBOSE);
                /*
                 * ***********************************************************************
                 * Serialize object for req_DH_params
                 */
                $p_bytes = strrev(Tools::packUnsignedInt($p));
                $q_bytes = strrev(Tools::packUnsignedInt($q));
                $new_nonce = Tools::random(32);
                $data_unserialized = ['_' => 'p_q_inner_data'.($expires_in < 0 ? '' : '_temp').'_dc', 'pq' => $pq_bytes, 'p' => $p_bytes, 'q' => $q_bytes, 'nonce' => $nonce, 'server_nonce' => $server_nonce, 'new_nonce' => $new_nonce, 'expires_in' => $expires_in, 'dc' => $this->datacenter];
                $p_q_inner_data = ($this->API->getTL()->serializeObject(['type' => ''], $data_unserialized, 'p_q_inner_data'));
                /*
                 * ***********************************************************************
                 * Encrypt serialized object
                 */
                if (\strlen($p_q_inner_data) > 144) {
                    throw new SecurityException('p_q_inner_data is too long!');
                }
                $data_with_padding = $p_q_inner_data.Tools::random(192 - \strlen($p_q_inner_data));
                $data_pad_reversed = strrev($data_with_padding);
                do {
                    $temp_key = Tools::random(32);
                    $data_with_hash = $data_pad_reversed.hash('sha256', $temp_key.$data_with_padding, true);
                    $aes_encrypted = Crypt::igeEncrypt($data_with_hash, $temp_key, str_repeat("\0", 32));
                    $temp_key_xor = $temp_key ^ hash('sha256', $aes_encrypted, true);
                    $key_aes_encrypted_bigint = new BigInteger($temp_key_xor.$aes_encrypted, 256);
                } while ($key_aes_encrypted_bigint->compare($key->n) >= 0);
                $encrypted_data = $key->encrypt($key_aes_encrypted_bigint);
                $this->API->logger('Starting Diffie Hellman key exchange', Logger::VERBOSE);
                /*
                 * ***********************************************************************
                 * Starting Diffie Hellman key exchange, Server authentication
                 * @method req_DH_params
                 * @param [
                 *         int128         $nonce                             : The value of nonce is selected randomly by the client (random number) and identifies the client within this communication
                 *         int128        $server_nonce                    : The value of server_nonce is selected randomly by the server
                 *         string        $p                                : The value of BigInteger
                 *         string        $q                                : The value of BigInteger
                 *         long        $public_key_fingerprint            : This is our key in the server_public_key_fingerprints vector
                 *         string        $encrypted_data
                 * ]
                 * @return Server_DH_Params [
                 *         int128         $nonce                         : The value of nonce is selected randomly by the server
                 *         int128         $server_nonce                     : The value of server_nonce is selected randomly by the server
                 *         string         $new_nonce_hash                    : Return this value if server responds with server_DH_params_fail
                 *         string         $encrypted_answer                : Return this value if server responds with server_DH_params_ok
                 * ]
                 */
                $server_dh_params = $this->methodCallAsyncRead('req_DH_params', ['nonce' => $nonce, 'server_nonce' => $server_nonce, 'p' => $p_bytes, 'q' => $q_bytes, 'public_key_fingerprint' => $key->fp, 'encrypted_data' => $encrypted_data]);
                /*
                 * ***********************************************************************
                 * Check if the client's nonce and the server's nonce are the same
                 */
                if ($nonce != $server_dh_params['nonce']) {
                    throw new SecurityException('wrong nonce.');
                }
                /*
                 * ***********************************************************************
                 * Check if server_nonce and new server_nonce are the same
                 */
                if ($server_nonce != $server_dh_params['server_nonce']) {
                    throw new SecurityException('wrong server nonce.');
                }
                /*
                 * ***********************************************************************
                 * Check valid new nonce hash if return from server
                 * new nonce hash return in server_DH_params_fail
                 */
                if (isset($server_dh_params['new_nonce_hash']) && substr(sha1($new_nonce), -32) != $server_dh_params['new_nonce_hash']) {
                    throw new SecurityException('wrong new nonce hash.');
                }
                /*
                 * ***********************************************************************
                 * Get key, iv and decrypt answer
                 */
                $encrypted_answer = $server_dh_params['encrypted_answer'];
                $tmp_aes_key = sha1($new_nonce.$server_nonce, true).substr(sha1($server_nonce.$new_nonce, true), 0, 12);
                $tmp_aes_iv = substr(sha1($server_nonce.$new_nonce, true), 12, 8).sha1($new_nonce.$new_nonce, true).substr($new_nonce, 0, 4);
                $answer_with_hash = Crypt::igeDecrypt($encrypted_answer, $tmp_aes_key, $tmp_aes_iv);
                /*
                 * ***********************************************************************
                 * Separate answer and hash
                 */
                $answer_hash = substr($answer_with_hash, 0, 20);
                /** @var string */
                $answer = substr($answer_with_hash, 20);
                /*
                 * ***********************************************************************
                 * Deserialize answer
                 * @return Server_DH_inner_data [
                 *         int128         $nonce                             : The value of nonce is selected randomly by the client (random number) and identifies the client within this communication
                 *         int128        $server_nonce                    : The value of server_nonce is selected randomly by the server
                 *         int            $g
                 *         string        $dh_prime
                 *         string        $g_a
                 *         int            $server_time
                 * ]
                 */
                $server_DH_inner_data = $this->API->getTL()->deserialize($answer, ['type' => '']);
                /*
                 * ***********************************************************************
                 * Do some checks
                 */
                $server_DH_inner_data_length = $this->API->getTL()->getLength($answer);
                if (sha1(substr($answer, 0, $server_DH_inner_data_length), true) != $answer_hash) {
                    throw new SecurityException('answer_hash mismatch.');
                }
                if ($nonce != $server_DH_inner_data['nonce']) {
                    throw new SecurityException('wrong nonce');
                }
                if ($server_nonce != $server_DH_inner_data['server_nonce']) {
                    throw new SecurityException('wrong server nonce');
                }
                $g = new BigInteger($server_DH_inner_data['g']);
                $g_a = new BigInteger((string) $server_DH_inner_data['g_a'], 256);
                $dh_prime = new BigInteger((string) $server_DH_inner_data['dh_prime'], 256);
                /*
                 * ***********************************************************************
                 * Time delta
                 */
                $server_time = $server_DH_inner_data['server_time'];
                $this->time_delta = $server_time - time();
                $this->API->logger(sprintf('Server-client time delta = %.1f s', $this->time_delta), Logger::VERBOSE);
                Crypt::checkPG($dh_prime, $g);
                Crypt::checkG($g_a, $dh_prime);
                for ($retry_id = 0; $retry_id <= $this->API->settings->getAuth()->getMaxAuthTries(); $retry_id++) {
                    $this->API->logger('Generating b...', Logger::VERBOSE);
                    $b = new BigInteger(Tools::random(256), 256);
                    $this->API->logger('Generating g_b...', Logger::VERBOSE);
                    $g_b = $g->powMod($b, $dh_prime);
                    Crypt::checkG($g_b, $dh_prime);
                    /*
                     * ***********************************************************************
                     * Check validity of g_b
                     * 1 < g_b < dh_prime - 1
                     */
                    $this->API->logger('Executing g_b check...', Logger::VERBOSE);
                    if ($g_b->compare(Magic::$one) <= 0 || $g_b->compare($dh_prime->subtract(Magic::$one)) >= 0) {
                        throw new SecurityException('g_b is invalid (1 < g_b < dh_prime - 1 is false).');
                    }
                    $this->API->logger('Preparing client_DH_inner_data...', Logger::VERBOSE);
                    $g_b_str = $g_b->toBytes();
                    /*
                     * ***********************************************************************
                     * serialize client_DH_inner_data
                     * @method client_DH_inner_data
                     * @param Server_DH_inner_data [
                     *         int128         $nonce                             : The value of nonce is selected randomly by the client (random number) and identifies the client within this communication
                     *         int128        $server_nonce                    : The value of server_nonce is selected randomly by the server
                     *         long        $retry_id                        : First attempt
                     *         string        $g_b                            : g^b mod dh_prime
                     * ]
                     */
                    $data = ($this->API->getTL()->serializeObject(['type' => ''], ['_' => 'client_DH_inner_data', 'nonce' => $nonce, 'server_nonce' => $server_nonce, 'retry_id' => $retry_id, 'g_b' => $g_b_str], 'client_DH_inner_data'));
                    /*
                     * ***********************************************************************
                     * encrypt client_DH_inner_data
                     */
                    $data_with_sha = sha1($data, true).$data;
                    $data_with_sha_padded = $data_with_sha.Tools::random(Tools::posmod(-\strlen($data_with_sha), 16));
                    $encrypted_data = Crypt::igeEncrypt($data_with_sha_padded, $tmp_aes_key, $tmp_aes_iv);
                    $this->API->logger('Executing set_client_DH_params...', Logger::VERBOSE);
                    /*
                     * ***********************************************************************
                     * Send set_client_DH_params query
                     * @method set_client_DH_params
                     * @param Server_DH_inner_data [
                     *         int128         $nonce                             : The value of nonce is selected randomly by the client (random number) and identifies the client within this communication
                     *         int128        $server_nonce                    : The value of server_nonce is selected randomly by the server
                     *         string        $encrypted_data
                     * ]
                     * @return Set_client_DH_params_answer [
                     *         string         $_                                 : This value is dh_gen_ok, dh_gen_retry OR dh_gen_fail
                     *         int128         $server_nonce                     : The value of server_nonce is selected randomly by the server
                     *         int128         $new_nonce_hash1                : Return this value if server responds with dh_gen_ok
                     *         int128         $new_nonce_hash2                : Return this value if server responds with dh_gen_retry
                     *         int128         $new_nonce_hash2                : Return this value if server responds with dh_gen_fail
                     * ]
                     */
                    $Set_client_DH_params_answer = $this->methodCallAsyncRead('set_client_DH_params', ['nonce' => $nonce, 'server_nonce' => $server_nonce, 'encrypted_data' => $encrypted_data]);
                    /*
                     * ***********************************************************************
                     * Generate auth_key
                     */
                    $this->API->logger(
                        \extension_loaded('gmp') ? 'Generating authorization key...' : 'Generating authorization key (install gmp to speed up this process)...',
                        Logger::VERBOSE
                    );
                    $auth_key = $g_a->powMod($b, $dh_prime);
                    $auth_key_str = $auth_key->toBytes();
                    $auth_key_sha = sha1($auth_key_str, true);
                    $auth_key_aux_hash = substr($auth_key_sha, 0, 8);
                    $new_nonce_hash1 = substr(sha1($new_nonce.\chr(1).$auth_key_aux_hash, true), -16);
                    $new_nonce_hash2 = substr(sha1($new_nonce.\chr(2).$auth_key_aux_hash, true), -16);
                    $new_nonce_hash3 = substr(sha1($new_nonce.\chr(3).$auth_key_aux_hash, true), -16);
                    /*
                     * ***********************************************************************
                     * Check if the client's nonce and the server's nonce are the same
                     */
                    if ($Set_client_DH_params_answer['nonce'] != $nonce) {
                        throw new SecurityException('wrong nonce.');
                    }
                    /*
                     * ***********************************************************************
                     * Check if server_nonce and new server_nonce are the same
                     */
                    if ($Set_client_DH_params_answer['server_nonce'] != $server_nonce) {
                        throw new SecurityException('wrong server nonce');
                    }
                    /*
                     * ***********************************************************************
                     * Check Set_client_DH_params_answer type
                     */
                    switch ($Set_client_DH_params_answer['_']) {
                        case 'dh_gen_ok':
                            if ($Set_client_DH_params_answer['new_nonce_hash1'] != $new_nonce_hash1) {
                                throw new SecurityException('wrong new_nonce_hash1');
                            }
                            $this->API->logger('Diffie Hellman key exchange processed successfully!', Logger::VERBOSE);
                            $key = $expires_in < 0 ? new PermAuthKey() : new TempAuthKey();
                            if ($expires_in >= 0) {
                                \assert($key instanceof TempAuthKey);
                                $key->expires(time() + $expires_in);
                            }
                            $key->setServerSalt(substr($new_nonce, 0, 8) ^ substr($server_nonce, 0, 8));
                            $key->setAuthKey($auth_key_str);
                            $this->API->logger('Auth key generated', Logger::NOTICE);
                            return $key;
                        case 'dh_gen_retry':
                            if ($Set_client_DH_params_answer['new_nonce_hash2'] != $new_nonce_hash2) {
                                throw new SecurityException('wrong new_nonce_hash_2');
                            }
                            //repeat foreach
                            $this->API->logger('Retrying Auth', Logger::VERBOSE);
                            break;
                        case 'dh_gen_fail':
                            if ($Set_client_DH_params_answer['new_nonce_hash3'] != $new_nonce_hash3) {
                                throw new SecurityException('wrong new_nonce_hash_3');
                            }
                            $this->API->logger('Auth Failed', Logger::WARNING);
                            break 2;
                        default:
                            throw new SecurityException('Response Error');
                            break;
                    }
                }
            } catch (SecurityException|Exception|RPCErrorException $e) {
                $this->API->logger("An exception occurred while generating the authorization key in DC {$this->datacenter}: ".$e->getMessage().' in '.basename($e->getFile(), '.php').' on line '.$e->getLine().'. Retrying...', Logger::WARNING);
                $this->reconnect();
            } catch (Throwable $e) {
                $this->API->logger("An exception occurred while generating the authorization key in DC {$this->datacenter}: ".$e.PHP_EOL.' Retrying (try number '.$retry_id_total.')...', Logger::WARNING);
                $this->reconnect();
            }
        }
        if (!$cdn) {
            throw new SecurityException('Auth Failed, please check the logfile for more information, make sure to install https://prime.madelineproto.xyz!');
        }
        return null;
    }
}
