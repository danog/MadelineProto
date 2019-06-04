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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

use danog\MadelineProto\Exception;

/**
 * Manages the creation of the authorization key.
 *
 * https://core.telegram.org/mtproto/auth_key
 * https://core.telegram.org/mtproto/samples-auth_key
 */
trait AuthKeyHandler
{
    private $init_auth_dcs = [];
    private $pending_auth = false;

    public function create_auth_key_async($expires_in, $datacenter): \Generator
    {
        $req_pq = strpos($datacenter, 'cdn') ? 'req_pq' : 'req_pq_multi';
        for ($retry_id_total = 1; $retry_id_total <= $this->settings['max_tries']['authorization']; $retry_id_total++) {
            try {
                $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['req_pq'], \danog\MadelineProto\Logger::VERBOSE);
                /**
                 * ***********************************************************************
                 * Make pq request, DH exchange initiation.
                 *
                 * @method req_pq
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
                $nonce = $this->random(16);
                $ResPQ = yield $this->method_call_async_read($req_pq, ['nonce' => $nonce], ['datacenter' => $datacenter]);
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
                foreach ($this->rsa_keys as $curkey) {
                    if (in_array($curkey->fp, $ResPQ['server_public_key_fingerprints'])) {
                        $key = $curkey;
                    }
                }
                if (!isset($key)) {
                    throw new \danog\MadelineProto\SecurityException("Couldn't find any of our keys in the server_public_key_fingerprints vector.");
                }
                $pq_bytes = $ResPQ['pq'];
                $server_nonce = $ResPQ['server_nonce'];
                /*
                 * ***********************************************************************
                 * Compute p and q
                 */
                $pq = new \phpseclib\Math\BigInteger($pq_bytes, 256);
                $q = new \phpseclib\Math\BigInteger(0);
                $p = new \phpseclib\Math\BigInteger(\danog\PrimeModule::auto_single($pq->__toString()));
                if (!$p->equals(\danog\MadelineProto\Magic::$zero)) {
                    $q = $pq->divide($p)[0];
                    if ($p->compare($q) > 0) {
                        list($p, $q) = [$q, $p];
                    }
                }
                if (!$pq->equals($p->multiply($q))) {
                    $this->logger->logger('Automatic factorization failed, trying native CPP module', \danog\MadelineProto\Logger::ERROR);
                    $p = new \phpseclib\Math\BigInteger(\danog\PrimeModule::native_single_cpp($pq->__toString()));
                    if (!$p->equals(\danog\MadelineProto\Magic::$zero)) {
                        $q = $pq->divide($p)[0];
                        if ($p->compare($q) > 0) {
                            list($p, $q) = [$q, $p];
                        }
                    }

                    if (!$pq->equals($p->multiply($q))) {
                        $this->logger->logger('Automatic factorization failed, trying alt py module', \danog\MadelineProto\Logger::ERROR);
                        $p = new \phpseclib\Math\BigInteger(\danog\PrimeModule::python_single_alt($pq->__toString()));
                        if (!$p->equals(\danog\MadelineProto\Magic::$zero)) {
                            $q = $pq->divide($p)[0];
                            if ($p->compare($q) > 0) {
                                list($p, $q) = [$q, $p];
                            }
                        }

                        if (!$pq->equals($p->multiply($q))) {
                            $this->logger->logger('Automatic factorization failed, trying py module', \danog\MadelineProto\Logger::ERROR);
                            $p = new \phpseclib\Math\BigInteger(\danog\PrimeModule::python_single($pq->__toString()));
                            if (!$p->equals(\danog\MadelineProto\Magic::$zero)) {
                                $q = $pq->divide($p)[0];
                                if ($p->compare($q) > 0) {
                                    list($p, $q) = [$q, $p];
                                }
                            }

                            if (!$pq->equals($p->multiply($q))) {
                                $this->logger->logger('Automatic factorization failed, trying native module', \danog\MadelineProto\Logger::ERROR);
                                $p = new \phpseclib\Math\BigInteger(\danog\PrimeModule::native_single($pq->__toString()));
                                if (!$p->equals(\danog\MadelineProto\Magic::$zero)) {
                                    $q = $pq->divide($p)[0];
                                    if ($p->compare($q) > 0) {
                                        list($p, $q) = [$q, $p];
                                    }
                                }

                                if (!$pq->equals($p->multiply($q))) {
                                    $this->logger->logger('Automatic factorization failed, trying wolfram module', \danog\MadelineProto\Logger::ERROR);

                                    $p = new \phpseclib\Math\BigInteger(yield $this->wolfram_single_async($pq->__toString()));
                                    if (!$p->equals(\danog\MadelineProto\Magic::$zero)) {
                                        $q = $pq->divide($p)[0];
                                        if ($p->compare($q) > 0) {
                                            list($p, $q) = [$q, $p];
                                        }
                                    }

                                    if (!$pq->equals($p->multiply($q))) {
                                        throw new \danog\MadelineProto\SecurityException("Couldn't compute p and q, install prime.madelineproto.xyz to fix. Original pq: {$pq}, computed p: {$p}, computed q: {$q}, computed pq: ".$p->multiply($q));
                                    }
                                }
                            }
                        }
                    }
                }

                $this->logger->logger('Factorization '.$pq.' = '.$p.' * '.$q, \danog\MadelineProto\Logger::VERBOSE);
                /*
                 * ***********************************************************************
                 * Serialize object for req_DH_params
                 */
                $p_bytes = $p->toBytes();
                $q_bytes = $q->toBytes();
                $new_nonce = $this->random(32);
                $data_unserialized = ['pq' => $pq_bytes, 'p' => $p_bytes, 'q' => $q_bytes, 'nonce' => $nonce, 'server_nonce' => $server_nonce, 'new_nonce' => $new_nonce, 'expires_in' => $expires_in, 'dc' => preg_replace('|_.*|', '', $datacenter)];
                $p_q_inner_data = yield $this->serialize_object_async(['type' => 'p_q_inner_data'.($expires_in < 0 ? '' : '_temp')], $data_unserialized, 'p_q_inner_data');
                /*
                 * ***********************************************************************
                 * Encrypt serialized object
                 */
                $sha_digest = sha1($p_q_inner_data, true);
                $random_bytes = $this->random(255 - strlen($p_q_inner_data) - strlen($sha_digest));
                $to_encrypt = $sha_digest.$p_q_inner_data.$random_bytes;
                $encrypted_data = $key->encrypt($to_encrypt);
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
                $server_dh_params = yield $this->method_call_async_read('req_DH_params', ['nonce' => $nonce, 'server_nonce' => $server_nonce, 'p' => $p_bytes, 'q' => $q_bytes, 'public_key_fingerprint' => $key->fp, 'encrypted_data' => $encrypted_data], ['datacenter' => $datacenter]);
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
                if (isset($server_dh_params['new_nonce_hash']) && substr(sha1($new_nonce), -32) != $server_dh_params['new_nonce_hash']) {
                    throw new \danog\MadelineProto\SecurityException('wrong new nonce hash.');
                }
                /*
                 * ***********************************************************************
                 * Get key, iv and decrypt answer
                 */
                $encrypted_answer = $server_dh_params['encrypted_answer'];
                $tmp_aes_key = sha1($new_nonce.$server_nonce, true).substr(sha1($server_nonce.$new_nonce, true), 0, 12);
                $tmp_aes_iv = substr(sha1($server_nonce.$new_nonce, true), 12, 8).sha1($new_nonce.$new_nonce, true).substr($new_nonce, 0, 4);
                $answer_with_hash = $this->ige_decrypt($encrypted_answer, $tmp_aes_key, $tmp_aes_iv);
                /*
                 * ***********************************************************************
                 * Separate answer and hash
                 */
                $answer_hash = substr($answer_with_hash, 0, 20);
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
                $server_DH_inner_data = $this->deserialize($answer, ['type' => '']);
                /*
                 * ***********************************************************************
                 * Do some checks
                 */
                $server_DH_inner_data_length = $this->get_length($answer);
                if (sha1(substr($answer, 0, $server_DH_inner_data_length), true) != $answer_hash) {
                    throw new \danog\MadelineProto\SecurityException('answer_hash mismatch.');
                }
                if ($nonce != $server_DH_inner_data['nonce']) {
                    throw new \danog\MadelineProto\SecurityException('wrong nonce');
                }
                if ($server_nonce != $server_DH_inner_data['server_nonce']) {
                    throw new \danog\MadelineProto\SecurityException('wrong server nonce');
                }
                $g = new \phpseclib\Math\BigInteger($server_DH_inner_data['g']);
                $g_a = new \phpseclib\Math\BigInteger($server_DH_inner_data['g_a'], 256);
                $dh_prime = new \phpseclib\Math\BigInteger($server_DH_inner_data['dh_prime'], 256);
                /*
                 * ***********************************************************************
                 * Time delta
                 */
                $server_time = $server_DH_inner_data['server_time'];
                $this->datacenter->sockets[$datacenter]->time_delta = $server_time - time();
                $this->logger->logger(sprintf('Server-client time delta = %.1f s', $this->datacenter->sockets[$datacenter]->time_delta), \danog\MadelineProto\Logger::VERBOSE);
                $this->check_p_g($dh_prime, $g);
                $this->check_G($g_a, $dh_prime);
                for ($retry_id = 0; $retry_id <= $this->settings['max_tries']['authorization']; $retry_id++) {
                    $this->logger->logger('Generating b...', \danog\MadelineProto\Logger::VERBOSE);
                    $b = new \phpseclib\Math\BigInteger($this->random(256), 256);
                    $this->logger->logger('Generating g_b...', \danog\MadelineProto\Logger::VERBOSE);
                    $g_b = $g->powMod($b, $dh_prime);
                    $this->check_G($g_b, $dh_prime);
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
                    $data = yield $this->serialize_object_async(['type' => 'client_DH_inner_data'], ['nonce' => $nonce, 'server_nonce' => $server_nonce, 'retry_id' => $retry_id, 'g_b' => $g_b_str], 'client_DH_inner_data');
                    /*
                     * ***********************************************************************
                     * encrypt client_DH_inner_data
                     */
                    $data_with_sha = sha1($data, true).$data;
                    $data_with_sha_padded = $data_with_sha.$this->random($this->posmod(-strlen($data_with_sha), 16));
                    $encrypted_data = $this->ige_encrypt($data_with_sha_padded, $tmp_aes_key, $tmp_aes_iv);
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
                    $Set_client_DH_params_answer = yield $this->method_call_async_read('set_client_DH_params', ['nonce' => $nonce, 'server_nonce' => $server_nonce, 'encrypted_data' => $encrypted_data], ['datacenter' => $datacenter]);
                    /*
                     * ***********************************************************************
                     * Generate auth_key
                     */
                    $this->logger->logger('Generating authorization key...', \danog\MadelineProto\Logger::VERBOSE);
                    $auth_key = $g_a->powMod($b, $dh_prime);
                    $auth_key_str = $auth_key->toBytes();
                    $auth_key_sha = sha1($auth_key_str, true);
                    $auth_key_aux_hash = substr($auth_key_sha, 0, 8);
                    $new_nonce_hash1 = substr(sha1($new_nonce.chr(1).$auth_key_aux_hash, true), -16);
                    $new_nonce_hash2 = substr(sha1($new_nonce.chr(2).$auth_key_aux_hash, true), -16);
                    $new_nonce_hash3 = substr(sha1($new_nonce.chr(3).$auth_key_aux_hash, true), -16);
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
                            $res_authorization['server_salt'] = substr($new_nonce, 0, 8 - 0) ^ substr($server_nonce, 0, 8 - 0);
                            $res_authorization['auth_key'] = $auth_key_str;
                            $res_authorization['id'] = substr($auth_key_sha, -8);
                            $res_authorization['connection_inited'] = false;
                            $this->logger->logger('Auth key generated', \danog\MadelineProto\Logger::NOTICE);

                            return $res_authorization;
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
            } catch (\danog\MadelineProto\SecurityException $e) {
                $this->logger->logger('An exception occurred while generating the authorization key: '.$e->getMessage().' in '.basename($e->getFile(), '.php').' on line '.$e->getLine().'. Retrying...', \danog\MadelineProto\Logger::WARNING);
            } catch (\danog\MadelineProto\Exception $e) {
                $this->logger->logger('An exception occurred while generating the authorization key: '.$e->getMessage().' in '.basename($e->getFile(), '.php').' on line '.$e->getLine().'. Retrying...', \danog\MadelineProto\Logger::WARNING);
                $req_pq = $req_pq === 'req_pq_multi' ? 'req_pq' : 'req_pq_multi';
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                $this->logger->logger('An RPCErrorException occurred while generating the authorization key: '.$e->getMessage().' Retrying (try number '.$retry_id_total.')...', \danog\MadelineProto\Logger::WARNING);
            }
        }
        if (strpos($datacenter, 'cdn') === false) {
            throw new \danog\MadelineProto\SecurityException('Auth Failed');
        }
    }

    public function check_G($g_a, $p)
    {
        /*
         * ***********************************************************************
         * Check validity of g_a
         * 1 < g_a < p - 1
         */
        $this->logger->logger('Executing g_a check (1/2)...', \danog\MadelineProto\Logger::VERBOSE);
        if ($g_a->compare(\danog\MadelineProto\Magic::$one) <= 0 || $g_a->compare($p->subtract(\danog\MadelineProto\Magic::$one)) >= 0) {
            throw new \danog\MadelineProto\SecurityException('g_a is invalid (1 < g_a < p - 1 is false).');
        }
        $this->logger->logger('Executing g_a check (2/2)...', \danog\MadelineProto\Logger::VERBOSE);
        if ($g_a->compare(\danog\MadelineProto\Magic::$twoe1984) < 0 || $g_a->compare($p->subtract(\danog\MadelineProto\Magic::$twoe1984)) >= 0) {
            throw new \danog\MadelineProto\SecurityException('g_a is invalid (2^1984 < g_a < p - 2^1984 is false).');
        }

        return true;
    }

    public function check_p_g($p, $g)
    {
        /*
         * ***********************************************************************
         * Check validity of dh_prime
         * Is it a prime?
         */
        $this->logger->logger('Executing p/g checks (1/2)...', \danog\MadelineProto\Logger::VERBOSE);
        if (!$p->isPrime()) {
            throw new \danog\MadelineProto\SecurityException("p isn't a safe 2048-bit prime (p isn't a prime).");
        }
        /*
         * ***********************************************************************
         * Check validity of p
         * Is (p - 1) / 2 a prime?
         *
         * Almost always fails
         */
        /*
        $this->logger->logger('Executing p/g checks (2/3)...', \danog\MadelineProto\Logger::VERBOSE);
        if (!$p->subtract(\danog\MadelineProto\Magic::$one)->divide(\danog\MadelineProto\Magic::$two)[0]->isPrime()) {
        throw new \danog\MadelineProto\SecurityException("p isn't a safe 2048-bit prime ((p - 1) / 2 isn't a prime).");
        }
         */
        /*
         * ***********************************************************************
         * Check validity of p
         * 2^2047 < p < 2^2048
         */
        $this->logger->logger('Executing p/g checks (2/2)...', \danog\MadelineProto\Logger::VERBOSE);
        if ($p->compare(\danog\MadelineProto\Magic::$twoe2047) <= 0 || $p->compare(\danog\MadelineProto\Magic::$twoe2048) >= 0) {
            throw new \danog\MadelineProto\SecurityException("g isn't a safe 2048-bit prime (2^2047 < p < 2^2048 is false).");
        }
        /*
         * ***********************************************************************
         * Check validity of g
         * 1 < g < p - 1
         */
        $this->logger->logger('Executing g check...', \danog\MadelineProto\Logger::VERBOSE);
        if ($g->compare(\danog\MadelineProto\Magic::$one) <= 0 || $g->compare($p->subtract(\danog\MadelineProto\Magic::$one)) >= 0) {
            throw new \danog\MadelineProto\SecurityException('g is invalid (1 < g < p - 1 is false).');
        }

        return true;
    }

    public function get_dh_config_async()
    {
        $dh_config = yield $this->method_call_async_read('messages.getDhConfig', ['version' => $this->dh_config['version'], 'random_length' => 0], ['datacenter' => $this->datacenter->curdc]);
        if ($dh_config['_'] === 'messages.dhConfigNotModified') {
            $this->logger->logger(\danog\MadelineProto\Logger::VERBOSE, ['DH configuration not modified']);

            return $this->dh_config;
        }
        $dh_config['p'] = new \phpseclib\Math\BigInteger($dh_config['p'], 256);
        $dh_config['g'] = new \phpseclib\Math\BigInteger($dh_config['g']);
        $this->check_p_g($dh_config['p'], $dh_config['g']);

        return $this->dh_config = $dh_config;
    }

    public function bind_temp_auth_key_async($expires_in, $datacenter)
    {
        for ($retry_id_total = 1; $retry_id_total <= $this->settings['max_tries']['authorization']; $retry_id_total++) {
            try {
                $this->logger->logger('Binding authorization keys...', \danog\MadelineProto\Logger::VERBOSE);
                $nonce = $this->random(8);
                $expires_at = time() + $expires_in;
                $temp_auth_key_id = $this->datacenter->sockets[$datacenter]->temp_auth_key['id'];
                $perm_auth_key_id = $this->datacenter->sockets[$datacenter]->auth_key['id'];
                $temp_session_id = $this->datacenter->sockets[$datacenter]->session_id;
                $message_data = yield $this->serialize_object_async(['type' => 'bind_auth_key_inner'], ['nonce' => $nonce, 'temp_auth_key_id' => $temp_auth_key_id, 'perm_auth_key_id' => $perm_auth_key_id, 'temp_session_id' => $temp_session_id, 'expires_at' => $expires_at], 'bind_temp_auth_key_inner');
                $message_id = $this->datacenter->sockets[$datacenter]->generate_message_id();
                $seq_no = 0;
                $encrypted_data = $this->random(16).$message_id.pack('VV', $seq_no, strlen($message_data)).$message_data;
                $message_key = substr(sha1($encrypted_data, true), -16);
                $padding = $this->random($this->posmod(-strlen($encrypted_data), 16));
                list($aes_key, $aes_iv) = $this->old_aes_calculate($message_key, $this->datacenter->sockets[$datacenter]->auth_key['auth_key']);
                $encrypted_message = $this->datacenter->sockets[$datacenter]->auth_key['id'].$message_key.$this->ige_encrypt($encrypted_data.$padding, $aes_key, $aes_iv);
                $res = yield $this->method_call_async_read('auth.bindTempAuthKey', ['perm_auth_key_id' => $perm_auth_key_id, 'nonce' => $nonce, 'expires_at' => $expires_at, 'encrypted_message' => $encrypted_message], ['msg_id' => $message_id, 'datacenter' => $datacenter]);
                if ($res === true) {
                    $this->logger->logger('Successfully binded temporary and permanent authorization keys, DC '.$datacenter, \danog\MadelineProto\Logger::NOTICE);
                    $this->datacenter->sockets[$datacenter]->temp_auth_key['bound'] = true;
                    $this->datacenter->sockets[$datacenter]->writer->resume();

                    return true;
                }
            } catch (\danog\MadelineProto\SecurityException $e) {
                $this->logger->logger('An exception occurred while generating the authorization key: '.$e->getMessage().' Retrying (try number '.$retry_id_total.')...', \danog\MadelineProto\Logger::WARNING);
            } catch (\danog\MadelineProto\Exception $e) {
                $this->logger->logger('An exception occurred while generating the authorization key: '.$e->getMessage().' Retrying (try number '.$retry_id_total.')...', \danog\MadelineProto\Logger::WARNING);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                $this->logger->logger('An RPCErrorException occurred while generating the authorization key: '.$e->getMessage().' Retrying (try number '.$retry_id_total.')...', \danog\MadelineProto\Logger::WARNING);
            }
        }

        throw new \danog\MadelineProto\SecurityException('An error occurred while binding temporary and permanent authorization keys.');
    }

    public function wolfram_single_async($what)
    {
        $code = yield $this->datacenter->fileGetContents('http://www.wolframalpha.com/api/v1/code');
        $query = 'Do prime factorization of '.$what;
        $params = [
            'async'         => true,
            'banners'       => 'raw',
            'debuggingdata' => false,
            'format'        => 'moutput',
            'formattimeout' => 8,
            'input'         => $query,
            'output'        => 'JSON',
            'proxycode'     => json_decode($code, true)['code'],
        ];
        $url = 'https://www.wolframalpha.com/input/json.jsp?'.http_build_query($params);

        $request = (new Request($url))->withHeader('referer', 'https://www.wolframalpha.com/input/?i='.urlencode($query));

        $res = json_decode(yield (yield $this->datacenter->getHTTPClient()->request($request))->getBody(), true);
        if (!isset($res['queryresult']['pods'])) {
            return false;
        }
        $fres = false;
        foreach ($res['queryresult']['pods'] as $cur) {
            if ($cur['id'] === 'Divisors') {
                $fres = explode(', ', preg_replace(["/{\d+, /", "/, \d+}$/"], '', $cur['subpods'][0]['moutput']));
                break;
            }
        }
        if (is_array($fres)) {
            $fres = $fres[0];

            $newval = intval($fres);
            if (is_int($newval)) {
                $fres = $newval;
            }

            return $fres;
        }

        return false;
    }

    public function init_authorization_async()
    {
        if ($this->pending_auth) {
            return;
        }
        $initing = $this->initing_authorization;

        $this->initing_authorization = true;

        try {
            $dcs = [];
            $postpone = [];
            foreach ($this->datacenter->sockets as $id => $socket) {
                if (strpos($id, 'media') !== false) {
                    $oid = intval($id);
                    if (isset($dcs[$oid])) {
                        $postpone[$id] = $socket;
                    }
                    continue;
                }
                if (isset($this->init_auth_dcs[$id])) {
                    $this->pending_auth = true;
                    continue;
                }
                $dcs[$id] = function () use ($id, $socket) {
                    return $this->init_authorization_socket_async($id, $socket);
                };
            }
            if ($dcs) {
                $first = array_shift($dcs)();
                yield $first;
            }

            foreach ($dcs as $id => &$dc) {
                $dc = $dc();
            }
            yield $this->all($dcs);

            foreach ($postpone as $id => $socket) {
                yield $this->init_authorization_socket_async($id, $socket);
            }

            if ($this->pending_auth && empty($this->init_auth_dcs)) {
                $this->pending_auth = false;
                yield $this->init_authorization_async();
            }
        } finally {
            $this->pending_auth = false;
            $this->initing_authorization = $initing;
        }
    }

    public function init_authorization_socket_async($id, $socket)
    {
        $this->init_auth_dcs[$id] = true;

        try {
            if ($socket->session_id === null) {
                $socket->session_id = $this->random(8);
                $socket->session_in_seq_no = 0;
                $socket->session_out_seq_no = 0;
            }
            $cdn = strpos($id, 'cdn');
            $media = strpos($id, 'media');
            if ($socket->temp_auth_key === null || $socket->auth_key === null) {
                $dc_config_number = isset($this->settings['connection_settings'][$id]) ? $id : 'all';
                if ($socket->auth_key === null && !$cdn && !$media) {
                    $this->logger->logger(sprintf(\danog\MadelineProto\Lang::$current_lang['gen_perm_auth_key'], $id), \danog\MadelineProto\Logger::NOTICE);
                    $socket->auth_key = yield $this->create_auth_key_async(-1, $id);
                    $socket->authorized = false;
                } elseif ($socket->auth_key === null && $media) {
                    $socket->auth_key = $this->datacenter->sockets[intval($id)]->auth_key;
                    $socket->authorized = &$this->datacenter->sockets[intval($id)]->authorized;
                }
                if ($media) {
                    $socket->authorized = &$this->datacenter->sockets[intval($id)]->authorized;
                }
                if ($this->settings['connection_settings'][$dc_config_number]['pfs']) {
                    if (!$cdn) {
                        $this->logger->logger(sprintf(\danog\MadelineProto\Lang::$current_lang['gen_temp_auth_key'], $id), \danog\MadelineProto\Logger::NOTICE);

                        //$authorized = $socket->authorized;
                        //$socket->authorized = false;

                        $socket->temp_auth_key = null;
                        $socket->temp_auth_key = yield $this->create_auth_key_async($this->settings['authorization']['default_temp_auth_key_expires_in'], $id);
                        yield $this->bind_temp_auth_key_async($this->settings['authorization']['default_temp_auth_key_expires_in'], $id);

                        $config = yield $this->method_call_async_read('help.getConfig', [], ['datacenter' => $id]);

                        yield $this->sync_authorization_async($id);
                        yield $this->get_config_async($config);
                    } elseif ($socket->temp_auth_key === null) {
                        $this->logger->logger(sprintf(\danog\MadelineProto\Lang::$current_lang['gen_temp_auth_key'], $id), \danog\MadelineProto\Logger::NOTICE);
                        $socket->temp_auth_key = yield $this->create_auth_key_async($this->settings['authorization']['default_temp_auth_key_expires_in'], $id);
                    }
                } else {
                    if (!$cdn) {
                        $socket->temp_auth_key = $socket->auth_key;
                        $config = yield $this->method_call_async_read('help.getConfig', [], ['datacenter' => $id]);
                        yield $this->sync_authorization_async($id);
                        yield $this->get_config_async($config);
                    } elseif ($socket->temp_auth_key === null) {
                        $this->logger->logger(sprintf(\danog\MadelineProto\Lang::$current_lang['gen_temp_auth_key'], $id), \danog\MadelineProto\Logger::NOTICE);
                        $socket->temp_auth_key = yield $this->create_auth_key_async($this->settings['authorization']['default_temp_auth_key_expires_in'], $id);
                    }
                }
            } elseif (!$cdn) {
                yield $this->sync_authorization_async($id);
            }
        } finally {
            unset($this->init_auth_dcs[$id]);
        }
    }

    public function sync_authorization_async($id)
    {
        if (!isset($this->datacenter->sockets[$id])) {
            return false;
        }
        $socket = $this->datacenter->sockets[$id];
        if ($this->authorized === self::LOGGED_IN && $socket->authorized === false) {
            foreach ($this->datacenter->sockets as $authorized_dc_id => $authorized_socket) {
                if ($this->authorized_dc !== -1 && $authorized_dc_id !== $this->authorized_dc) {
                    continue;
                }
                if ($authorized_socket->temp_auth_key !== null && $authorized_socket->auth_key !== null && $authorized_socket->authorized === true && $this->authorized === self::LOGGED_IN && $socket->authorized === false && strpos($authorized_dc_id, 'cdn') === false) {
                    try {
                        $this->logger->logger('Trying to copy authorization from dc '.$authorized_dc_id.' to dc '.$id);
                        $exported_authorization = yield $this->method_call_async_read('auth.exportAuthorization', ['dc_id' => preg_replace('|_.*|', '', $id)], ['datacenter' => $authorized_dc_id]);
                        $authorization = yield $this->method_call_async_read('auth.importAuthorization', $exported_authorization, ['datacenter' => $id]);
                        $socket->authorized = true;
                        break;
                    } catch (\danog\MadelineProto\Exception $e) {
                        $this->logger->logger('Failure while syncing authorization from DC '.$authorized_dc_id.' to DC '.$id.': '.$e->getMessage(), \danog\MadelineProto\Logger::ERROR);
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                        $this->logger->logger('Failure while syncing authorization from DC '.$authorized_dc_id.' to DC '.$id.': '.$e->getMessage(), \danog\MadelineProto\Logger::ERROR);
                        if ($e->rpc === 'DC_ID_INVALID') {
                            break;
                        }
                    }
                    // Turns out this DC isn't authorized after all
                }
            }
        }
    }
}
