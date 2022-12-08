<?php

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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoSession;

use Amp\Http\Client\Request;
use danog\MadelineProto\DataCenterConnection;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\PermAuthKey;
use danog\MadelineProto\MTProto\TempAuthKey;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\SecurityException;
use danog\MadelineProto\Tools;
use danog\PrimeModule;

use phpseclib3\Math\BigInteger;

/**
 * Manages the creation of the authorization key.
 *
 * https://core.telegram.org/mtproto/auth_key
 * https://core.telegram.org/mtproto/samples-auth_key
 *
 * @property DataCenterConnection $shared
 * @property string $datacenter
 * @property MTProto $API
 * @property Logger $logger
 */
trait AuthKeyHandler
{
    /**
     * Create authorization key.
     *
     *
     * @psalm-return \Generator<mixed, mixed|string, mixed, ($temp is false ? \danog\MadelineProto\MTProto\PermAuthKey : \danog\MadelineProto\MTProto\TempAuthKey)|null>
     */
    public function createAuthKey(bool $temp): \Generator
    {
        $expires_in = $temp ? $this->API->settings->getAuth()->getDefaultTempAuthKeyExpiresIn() : -1;
        $cdn = $this->isCDN();
        $media = $this->isMedia();
        $test = $this->API->settings->getConnection()->getTestMode();
        $datacenter_id = \preg_replace('|_.*|', '', $this->datacenter) + ($this->API->settings->getConnection()->getTestMode() ? 10000 : 0);
        if ($media) {
            $datacenter_id = -$datacenter_id;
        }

        for ($retry_id_total = 1; $retry_id_total <= $this->API->settings->getAuth()->getMaxAuthTries(); $retry_id_total++) {
            try {
                $this->logger->logger("Requesting pq...", \danog\MadelineProto\Logger::VERBOSE);
                /**
                 * ***********************************************************************
                 * Make pq request, DH exchange initiation.
                 *
                 * @method req_pq_multi
                 *
                 * @param [
                 *         int128         $nonce                             : The value of nonce is selected randomly by the client (random number) and identifies the client within this communication
                 * ]
                 *
                 * @return ResPQ [
                 *               int128         $nonce                             : The value of nonce is selected randomly by the server
                 *               int128         $server_nonce                         : The value of server_nonce is selected randomly by the server
                 *               string         $pq                             : This is a representation of a natural number (in binary big endian format). This number is the product of two different odd prime numbers
                 *               Vector long    $server_public_key_fingerprints                : This is a list of public RSA key fingerprints
                 *               ]
                 */
                $nonce = \danog\MadelineProto\Tools::random(16);
                $ResPQ = yield from $this->methodCallAsyncRead('req_pq_multi', ['nonce' => $nonce]);
                /*
                 * ***********************************************************************
                 * Check if the client's nonce and the server's nonce are the same
                 */
                if ($ResPQ['nonce'] !== $nonce) {
                    throw new \danog\MadelineProto\SecurityException('wrong nonce');
                }
                /*
                 * ***********************************************************************
                 * Find our key in the server_public_key_fingerprints vector
                 */
                foreach ($this->API->getRsaKeys($test, $cdn) as $curkey) {
                    if (\in_array($curkey->fp, $ResPQ['server_public_key_fingerprints'])) {
                        $key = $curkey;
                    }
                }
                if (!isset($key)) {
                    if ($cdn) {
                        $this->logger->logger("Could not find required CDN public key, postponing CDN handshake...");
                        return;
                    }
                    throw new \danog\MadelineProto\SecurityException("Couldn't find any of our keys in the server_public_key_fingerprints vector.");
                }
                $pq_bytes = $ResPQ['pq'];
                $server_nonce = $ResPQ['server_nonce'];
                /*
                 * ***********************************************************************
                 * Compute p and q
                 */
                $ok = false;
                $pq = Tools::unpackSignedLong(\strrev($pq_bytes));
                foreach ([
                    'native_single_cpp',
                    'python_single_alt',
                    'python_single',
                    'native_single',
                ] as $method) {
                    $this->logger->logger("Factorizing with $method (please wait, might take a while)");
                    if ($method !== 'native_single_cpp') {
                        $this->logger->logger("Install https://prime.madelineproto.xyz and the FFI extension to speed this up!");
                    }

                    $p = 0;
                    $q = 0;
                    try {
                        if ($method === 'wolfram') {
                            $p = yield from $this->wolframSingle($pq);
                        } else {
                            $p = PrimeModule::$method($pq);
                        }
                    } catch (\Throwable $e) {
                        $this->logger->logger("While factorizing with $method: $e");
                    }

                    if ($p) {
                        $q = $pq / $p;
                        if ($p > $q) {
                            list($p, $q) = [$q, $p];
                        }
                        if ($pq === $p*$q) {
                            $ok = true;
                            break;
                        }
                    }
                }
                if (!$ok) {
                    throw new \danog\MadelineProto\SecurityException("Couldn't compute p and q, install prime.madelineproto.xyz to fix. Original pq: {$pq}, computed p: {$p}, computed q: {$q}, computed pq: ".$p*$q);
                }
                $this->logger->logger('Factorization '.$pq.' = '.$p.' * '.$q, \danog\MadelineProto\Logger::VERBOSE);
                /*
                 * ***********************************************************************
                 * Serialize object for req_DH_params
                 */
                $p_bytes = \strrev(Tools::packUnsignedInt($p));
                $q_bytes = \strrev(Tools::packUnsignedInt($q));
                $new_nonce = \danog\MadelineProto\Tools::random(32);
                $data_unserialized = ['_' => 'p_q_inner_data'.($expires_in < 0 ? '' : '_temp').'_dc', 'pq' => $pq_bytes, 'p' => $p_bytes, 'q' => $q_bytes, 'nonce' => $nonce, 'server_nonce' => $server_nonce, 'new_nonce' => $new_nonce, 'expires_in' => $expires_in, 'dc' => $datacenter_id];
                $p_q_inner_data = (yield from $this->API->getTL()->serializeObject(['type' => ''], $data_unserialized, 'p_q_inner_data'));
                /*
                 * ***********************************************************************
                 * Encrypt serialized object
                 */
                if (\strlen($p_q_inner_data) > 144) {
                    throw new SecurityException('p_q_inner_data is too long!');
                }
                $data_with_padding = $p_q_inner_data.Tools::random(192 - \strlen($p_q_inner_data));
                $data_pad_reversed = \strrev($data_with_padding);
                do {
                    $temp_key = Tools::random(32);
                    $data_with_hash = $data_pad_reversed.\hash('sha256', $temp_key.$data_with_padding, true);
                    $aes_encrypted = Crypt::igeEncrypt($data_with_hash, $temp_key, \str_repeat("\0", 32));
                    $temp_key_xor = $temp_key ^ \hash('sha256', $aes_encrypted, true);
                    $key_aes_encrypted_bigint = new BigInteger($temp_key_xor.$aes_encrypted, 256);
                } while ($key_aes_encrypted_bigint->compare($key->n) >= 0);
                $encrypted_data = $key->encrypt($key_aes_encrypted_bigint);
                $this->logger->logger('Starting Diffie Hellman key exchange', \danog\MadelineProto\Logger::VERBOSE);
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
                //
                $server_dh_params = yield from $this->methodCallAsyncRead('req_DH_params', ['nonce' => $nonce, 'server_nonce' => $server_nonce, 'p' => $p_bytes, 'q' => $q_bytes, 'public_key_fingerprint' => $key->fp, 'encrypted_data' => $encrypted_data]);
                /*
                 * ***********************************************************************
                 * Check if the client's nonce and the server's nonce are the same
                 */
                if ($nonce != $server_dh_params['nonce']) {
                    throw new \danog\MadelineProto\SecurityException('wrong nonce.');
                }
                /*
                 * ***********************************************************************
                 * Check if server_nonce and new server_nonce are the same
                 */
                if ($server_nonce != $server_dh_params['server_nonce']) {
                    throw new \danog\MadelineProto\SecurityException('wrong server nonce.');
                }
                /*
                 * ***********************************************************************
                 * Check valid new nonce hash if return from server
                 * new nonce hash return in server_DH_params_fail
                 */
                if (isset($server_dh_params['new_nonce_hash']) && \substr(\sha1($new_nonce), -32) != $server_dh_params['new_nonce_hash']) {
                    throw new \danog\MadelineProto\SecurityException('wrong new nonce hash.');
                }
                /*
                 * ***********************************************************************
                 * Get key, iv and decrypt answer
                 */
                $encrypted_answer = $server_dh_params['encrypted_answer'];
                $tmp_aes_key = \sha1($new_nonce.$server_nonce, true).\substr(\sha1($server_nonce.$new_nonce, true), 0, 12);
                $tmp_aes_iv = \substr(\sha1($server_nonce.$new_nonce, true), 12, 8).\sha1($new_nonce.$new_nonce, true).\substr($new_nonce, 0, 4);
                $answer_with_hash = Crypt::igeDecrypt($encrypted_answer, $tmp_aes_key, $tmp_aes_iv);
                /*
                 * ***********************************************************************
                 * Separate answer and hash
                 */
                $answer_hash = \substr($answer_with_hash, 0, 20);
                /** @var string */
                $answer = \substr($answer_with_hash, 20);
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
                [$server_DH_inner_data] = $this->API->getTL()->deserialize($answer, ['type' => '']);
                /*
                 * ***********************************************************************
                 * Do some checks
                 */
                $server_DH_inner_data_length = $this->API->getTL()->getLength($answer);
                if (\sha1(\substr($answer, 0, $server_DH_inner_data_length), true) != $answer_hash) {
                    throw new \danog\MadelineProto\SecurityException('answer_hash mismatch.');
                }
                if ($nonce != $server_DH_inner_data['nonce']) {
                    throw new \danog\MadelineProto\SecurityException('wrong nonce');
                }
                if ($server_nonce != $server_DH_inner_data['server_nonce']) {
                    throw new \danog\MadelineProto\SecurityException('wrong server nonce');
                }
                $g = new BigInteger($server_DH_inner_data['g']);
                $g_a = new BigInteger((string) $server_DH_inner_data['g_a'], 256);
                $dh_prime = new BigInteger((string) $server_DH_inner_data['dh_prime'], 256);
                /*
                 * ***********************************************************************
                 * Time delta
                 */
                $server_time = $server_DH_inner_data['server_time'];
                $this->time_delta = $server_time - \time();
                $this->logger->logger(\sprintf('Server-client time delta = %.1f s', $this->time_delta), \danog\MadelineProto\Logger::VERBOSE);
                Crypt::checkPG($dh_prime, $g);
                Crypt::checkG($g_a, $dh_prime);
                for ($retry_id = 0; $retry_id <= $this->API->settings->getAuth()->getMaxAuthTries(); $retry_id++) {
                    $this->logger->logger('Generating b...', \danog\MadelineProto\Logger::VERBOSE);
                    $b = new BigInteger(\danog\MadelineProto\Tools::random(256), 256);
                    $this->logger->logger('Generating g_b...', \danog\MadelineProto\Logger::VERBOSE);
                    $g_b = $g->powMod($b, $dh_prime);
                    Crypt::checkG($g_b, $dh_prime);
                    /*
                     * ***********************************************************************
                     * Check validity of g_b
                     * 1 < g_b < dh_prime - 1
                     */
                    $this->logger->logger('Executing g_b check...', \danog\MadelineProto\Logger::VERBOSE);
                    if ($g_b->compare(\danog\MadelineProto\Magic::$one) <= 0 || $g_b->compare($dh_prime->subtract(\danog\MadelineProto\Magic::$one)) >= 0) {
                        throw new \danog\MadelineProto\SecurityException('g_b is invalid (1 < g_b < dh_prime - 1 is false).');
                    }
                    $this->logger->logger('Preparing client_DH_inner_data...', \danog\MadelineProto\Logger::VERBOSE);
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
                    $data = (yield from $this->API->getTL()->serializeObject(['type' => ''], ['_' => 'client_DH_inner_data', 'nonce' => $nonce, 'server_nonce' => $server_nonce, 'retry_id' => $retry_id, 'g_b' => $g_b_str], 'client_DH_inner_data'));
                    /*
                     * ***********************************************************************
                     * encrypt client_DH_inner_data
                     */
                    $data_with_sha = \sha1($data, true).$data;
                    $data_with_sha_padded = $data_with_sha.\danog\MadelineProto\Tools::random(\danog\MadelineProto\Tools::posmod(-\strlen($data_with_sha), 16));
                    $encrypted_data = Crypt::igeEncrypt($data_with_sha_padded, $tmp_aes_key, $tmp_aes_iv);
                    $this->logger->logger('Executing set_client_DH_params...', \danog\MadelineProto\Logger::VERBOSE);
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
                    $Set_client_DH_params_answer = yield from $this->methodCallAsyncRead('set_client_DH_params', ['nonce' => $nonce, 'server_nonce' => $server_nonce, 'encrypted_data' => $encrypted_data]);
                    /*
                     * ***********************************************************************
                     * Generate auth_key
                     */
                    $this->logger->logger('Generating authorization key...', \danog\MadelineProto\Logger::VERBOSE);
                    $auth_key = $g_a->powMod($b, $dh_prime);
                    $auth_key_str = $auth_key->toBytes();
                    $auth_key_sha = \sha1($auth_key_str, true);
                    $auth_key_aux_hash = \substr($auth_key_sha, 0, 8);
                    $new_nonce_hash1 = \substr(\sha1($new_nonce.\chr(1).$auth_key_aux_hash, true), -16);
                    $new_nonce_hash2 = \substr(\sha1($new_nonce.\chr(2).$auth_key_aux_hash, true), -16);
                    $new_nonce_hash3 = \substr(\sha1($new_nonce.\chr(3).$auth_key_aux_hash, true), -16);
                    /*
                     * ***********************************************************************
                     * Check if the client's nonce and the server's nonce are the same
                     */
                    if ($Set_client_DH_params_answer['nonce'] != $nonce) {
                        throw new \danog\MadelineProto\SecurityException('wrong nonce.');
                    }
                    /*
                     * ***********************************************************************
                     * Check if server_nonce and new server_nonce are the same
                     */
                    if ($Set_client_DH_params_answer['server_nonce'] != $server_nonce) {
                        throw new \danog\MadelineProto\SecurityException('wrong server nonce');
                    }
                    /*
                     * ***********************************************************************
                     * Check Set_client_DH_params_answer type
                     */
                    switch ($Set_client_DH_params_answer['_']) {
                        case 'dh_gen_ok':
                            if ($Set_client_DH_params_answer['new_nonce_hash1'] != $new_nonce_hash1) {
                                throw new \danog\MadelineProto\SecurityException('wrong new_nonce_hash1');
                            }
                            $this->logger->logger('Diffie Hellman key exchange processed successfully!', \danog\MadelineProto\Logger::VERBOSE);
                            $key = $expires_in < 0 ? new PermAuthKey() : new TempAuthKey();
                            if ($expires_in >= 0) {
                                $key->expires(\time() + $expires_in);
                            }
                            $key->setServerSalt(\substr($new_nonce, 0, 8) ^ \substr($server_nonce, 0, 8));
                            $key->setAuthKey($auth_key_str);
                            $this->logger->logger('Auth key generated', \danog\MadelineProto\Logger::NOTICE);
                            return $key;
                        case 'dh_gen_retry':
                            if ($Set_client_DH_params_answer['new_nonce_hash2'] != $new_nonce_hash2) {
                                throw new \danog\MadelineProto\SecurityException('wrong new_nonce_hash_2');
                            }
                            //repeat foreach
                            $this->logger->logger('Retrying Auth', \danog\MadelineProto\Logger::VERBOSE);
                            break;
                        case 'dh_gen_fail':
                            if ($Set_client_DH_params_answer['new_nonce_hash3'] != $new_nonce_hash3) {
                                throw new \danog\MadelineProto\SecurityException('wrong new_nonce_hash_3');
                            }
                            $this->logger->logger('Auth Failed', \danog\MadelineProto\Logger::WARNING);
                            break 2;
                        default:
                            throw new \danog\MadelineProto\SecurityException('Response Error');
                            break;
                    }
                }
            } catch (SecurityException|Exception|RPCErrorException $e) {
                $this->logger->logger("An exception occurred while generating the authorization key in DC {$this->datacenter}: ".$e->getMessage().' in '.\basename($e->getFile(), '.php').' on line '.$e->getLine().'. Retrying...', \danog\MadelineProto\Logger::WARNING);
                yield from $this->reconnect();
            } catch (\Throwable $e) {
                $this->logger->logger("An exception occurred while generating the authorization key in DC {$this->datacenter}: ".$e.PHP_EOL.' Retrying (try number '.$retry_id_total.')...', \danog\MadelineProto\Logger::WARNING);
                yield from $this->reconnect();
            }
        }
        if (!$cdn) {
            throw new \danog\MadelineProto\SecurityException('Auth Failed, please check the logfile for more information, make sure to install https://prime.madelineproto.xyz!');
        }
    }
    /**
     * Factorize number asynchronously using the wolfram API.
     *
     * @param string|integer $what Number to factorize
     *
     *
     * @psalm-return \Generator<int, \Amp\Promise<string>, mixed, false|int|string>
     */
    private function wolframSingle($what): \Generator
    {
        $code = (yield from $this->API->datacenter->fileGetContents('http://www.wolframalpha.com/api/v1/code'));
        $query = 'Do prime factorization of '.$what;
        $params = ['async' => true, 'banners' => 'raw', 'debuggingdata' => false, 'format' => 'moutput', 'formattimeout' => 8, 'input' => $query, 'output' => 'JSON', 'proxycode' => \json_decode($code, true)['code']];
        $url = 'https://www.wolframalpha.com/input/json.jsp?'.\http_build_query($params);
        $request = new Request($url);
        $request->setHeader('referer', 'https://www.wolframalpha.com/input/?i='.\urlencode($query));
        $res = \json_decode(yield (yield $this->API->datacenter->getHTTPClient()->request($request))->getBody()->buffer(), true);
        if (!isset($res['queryresult']['pods'])) {
            return false;
        }
        $fres = false;
        foreach ($res['queryresult']['pods'] as $cur) {
            if ($cur['id'] === 'Divisors') {
                $fres = \explode(', ', \preg_replace(["/{\\d+, /", "/, \\d+}\$/"], '', $cur['subpods'][0]['moutput']));
                break;
            }
        }
        if (\is_array($fres)) {
            $fres = $fres[0];
            $newval = \intval($fres);
            if (\is_int($newval)) {
                $fres = $newval;
            }
            return $fres;
        }
        return false;
    }
}
