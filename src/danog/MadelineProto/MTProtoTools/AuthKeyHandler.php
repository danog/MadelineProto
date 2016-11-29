<?php
/*
Copyright 2016 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\MTProtoTools;

/**
 * Manages the creation of the authorization key.
 *
 * https://core.telegram.org/mtproto/auth_key
 * https://core.telegram.org/mtproto/samples-auth_key
 */
class AuthKeyHandler extends AckHandler
{
    public function create_auth_key($expires_in = -1)
    {
        foreach ($this->range(0, $this->settings['max_tries']['authorization']) as $retry_id_total) {
            try {
                \danog\MadelineProto\Logger::log('Requesting pq');

                /**
                 * ***********************************************************************
                 * Make pq request, DH exchange initiation.
                 *
                 * @method req_pq
                 *
                 * @param [
                 * 		int128 		$nonce 							: The value of nonce is selected randomly by the client (random number) and identifies the client within this communication
                 * ]
                 *
                 * @return ResPQ [
                 *               int128 		$nonce 							: The value of nonce is selected randomly by the server
                 *               int128 		$server_nonce 					: The value of server_nonce is selected randomly by the server
                 *               string 		$pq 							: This is a representation of a natural number (in binary big endian format). This number is the product of two different odd prime numbers
                 *               Vector long $server_public_key_fingerprints : This is a list of public RSA key fingerprints
                 *               ]
                 */
                $nonce = \phpseclib\Crypt\Random::string(16);
                $ResPQ = $this->method_call('req_pq',
                    [
                        'nonce' => $nonce,
                    ]
                );

                /*
                * ***********************************************************************
                * Check if the client's nonce and the server's nonce are the same
                */
                if ($ResPQ['nonce'] !== $nonce) {
                    throw new \danog\MadelineProto\Exception('wrong nonce');
                }

                /*
                * ***********************************************************************
                * Find our key in the server_public_key_fingerprints vector
                */
                foreach ($ResPQ['server_public_key_fingerprints'] as $curfp) {
                    $curfp_biginteger = new \phpseclib\Math\BigInteger($curfp);
                    if ($this->key->fp->equals($curfp_biginteger)) {
                        $public_key_fingerprint = $curfp;
                        break;
                    }
                }

                if (!isset($public_key_fingerprint)) {
                    throw new \danog\MadelineProto\Exception("Couldn't find our key in the server_public_key_fingerprints vector.");
                }

                $pq_bytes = $ResPQ['pq'];
                $server_nonce = $ResPQ['server_nonce'];

                /*
                * ***********************************************************************
                * Compute p and q
                */
                $pq = new \phpseclib\Math\BigInteger($pq_bytes, 256);
                list($p, $q) = $this->PrimeFactors($pq);
                $p = new \phpseclib\Math\BigInteger($p);
                $q = new \phpseclib\Math\BigInteger($q);

                if ($p->compare($q) > 0) {
                    list($p, $q) = [$q, $p];
                }

                if (!($pq->equals($p->multiply($q)) && $p->compare($q) < 0)) {
                    throw new \danog\MadelineProto\Exception("couldn't compute p and q.");
                }

                \danog\MadelineProto\Logger::log('Factorization '.$pq.' = '.$p.' * '.$q);

                /*
                * ***********************************************************************
                * Serialize object for req_DH_params
                */
                $p_bytes = \danog\PHP\Struct::pack('>I', (string) $p);
                $q_bytes = \danog\PHP\Struct::pack('>I', (string) $q);

                $new_nonce = \phpseclib\Crypt\Random::string(32);

                $data_unserialized = [
                    'pq'              => $pq_bytes,
                    'p'               => $p_bytes,
                    'q'               => $q_bytes,
                    'nonce'           => $nonce,
                    'server_nonce'    => $server_nonce,
                    'new_nonce'       => $new_nonce,
                    'expires_in'      => $expires_in,
                ];
                $p_q_inner_data = $this->tl->serialize_object(['type' => 'p_q_inner_data'.(($expires_in < 0) ? '' : '_temp')], $data_unserialized);

                /*
                * ***********************************************************************
                * Encrypt serialized object
                */
                $sha_digest = sha1($p_q_inner_data, true);
                $random_bytes = \phpseclib\Crypt\Random::string(255 - strlen($p_q_inner_data) - strlen($sha_digest));
                $to_encrypt = $sha_digest.$p_q_inner_data.$random_bytes;
                $encrypted_data = $this->key->encrypt($to_encrypt);

                \danog\MadelineProto\Logger::log('Starting Diffie Hellman key exchange');
                /*
                * ***********************************************************************
                * Starting Diffie Hellman key exchange, Server authentication
                * @method req_DH_params
                * @param [
                * 		int128 		$nonce 							: The value of nonce is selected randomly by the client (random number) and identifies the client within this communication
                * 		int128		$server_nonce					: The value of server_nonce is selected randomly by the server
                * 		string		$p								: The value of BigInteger
                * 		string		$q								: The value of BigInteger
                * 		long		$public_key_fingerprint			: This is our key in the server_public_key_fingerprints vector
                * 		string		$encrypted_data
                * ]
                * @return Server_DH_Params [
                * 		int128 		$nonce 						: The value of nonce is selected randomly by the server
                * 		int128 		$server_nonce 					: The value of server_nonce is selected randomly by the server
                * 		string 		$new_nonce_hash					: Return this value if server responds with server_DH_params_fail
                * 		string 		$encrypted_answer				: Return this value if server responds with server_DH_params_ok
                * ]
                */
                //
                $server_dh_params = $this->method_call('req_DH_params',
                    [
                        'nonce'                  => $nonce,
                        'server_nonce'           => $server_nonce,
                        'p'                      => $p_bytes,
                        'q'                      => $q_bytes,
                        'public_key_fingerprint' => $public_key_fingerprint,
                        'encrypted_data'         => $encrypted_data,
                    ]
                );

                /*
                * ***********************************************************************
                * Check if the client's nonce and the server's nonce are the same
                */
                if ($nonce != $server_dh_params['nonce']) {
                    throw new \danog\MadelineProto\Exception('wrong nonce.');
                }

                /*
                * ***********************************************************************
                * Check if server_nonce and new server_nonce are the same
                */
                if ($server_nonce != $server_dh_params['server_nonce']) {
                    throw new \danog\MadelineProto\Exception('wrong server nonce.');
                }

                /*
                * ***********************************************************************
                * Check valid new nonce hash if return from server
                * new nonce hash return in server_DH_params_fail
                */
                if (isset($server_dh_params['new_nonce_hash']) && substr(sha1($new_nonce), -32) != $server_dh_params['new_nonce_hash']) {
                    throw new \danog\MadelineProto\Exception('wrong new nonce hash.');
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
                * 		int128 		$nonce 							: The value of nonce is selected randomly by the client (random number) and identifies the client within this communication
                * 		int128		$server_nonce					: The value of server_nonce is selected randomly by the server
                * 		int			$g
                * 		string		$dh_prime
                * 		string		$g_a
                * 		int			$server_time
                * ]
                */
                $server_DH_inner_data = $this->tl->deserialize($this->fopen_and_write('php://memory', 'rw+b', $answer));

                /*
                * ***********************************************************************
                * Do some checks
                */
                $server_DH_inner_data_length = $this->tl->get_length($this->fopen_and_write('php://memory', 'rw+b', $answer));
                if (sha1(substr($answer, 0, $server_DH_inner_data_length), true) != $answer_hash) {
                    throw new \danog\MadelineProto\Exception('answer_hash mismatch.');
                }

                if ($nonce != $server_DH_inner_data['nonce']) {
                    throw new \danog\MadelineProto\Exception('wrong nonce');
                }

                if ($server_nonce != $server_DH_inner_data['server_nonce']) {
                    throw new \danog\MadelineProto\Exception('wrong server nonce');
                }

                $g = new \phpseclib\Math\BigInteger($server_DH_inner_data['g']);
                $g_a = new \phpseclib\Math\BigInteger($server_DH_inner_data['g_a'], 256);
                $dh_prime = new \phpseclib\Math\BigInteger($server_DH_inner_data['dh_prime'], 256);

                /*
                * ***********************************************************************
                * Time delta
                */
                $server_time = $server_DH_inner_data['server_time'];
                $this->datacenter->time_delta = $server_time - time();

                \danog\MadelineProto\Logger::log(sprintf('Server-client time delta = %.1f s', $this->datacenter->time_delta));


                /*
                * ***********************************************************************
                * Define some needed numbers for BigInteger
                */
                \danog\MadelineProto\Logger::log('Executing dh_prime checks (0/3)...');
                $one = new \phpseclib\Math\BigInteger(1);
                //$two = new \phpseclib\Math\BigInteger(2);
                $twoe2047 = new \phpseclib\Math\BigInteger('16158503035655503650357438344334975980222051334857742016065172713762327569433945446598600705761456731844358980460949009747059779575245460547544076193224141560315438683650498045875098875194826053398028819192033784138396109321309878080919047169238085235290822926018152521443787945770532904303776199561965192760957166694834171210342487393282284747428088017663161029038902829665513096354230157075129296432088558362971801859230928678799175576150822952201848806616643615613562842355410104862578550863465661734839271290328348967522998634176499319107762583194718667771801067716614802322659239302476074096777926805529798115328');
                $twoe2048 = new \phpseclib\Math\BigInteger('32317006071311007300714876688669951960444102669715484032130345427524655138867890893197201411522913463688717960921898019494119559150490921095088152386448283120630877367300996091750197750389652106796057638384067568276792218642619756161838094338476170470581645852036305042887575891541065808607552399123930385521914333389668342420684974786564569494856176035326322058077805659331026192708460314150258592864177116725943603718461857357598351152301645904403697613233287231227125684710820209725157101726931323469678542580656697935045997268352998638215525166389437335543602135433229604645318478604952148193555853611059596230656');

                /*
                * ***********************************************************************
                * Check validity of dh_prime
                * Is it a prime?
                */
                \danog\MadelineProto\Logger::log('Executing dh_prime checks (1/3)...');
                if (!$dh_prime->isPrime()) {
                    throw new \danog\MadelineProto\Exception("dh_prime isn't a safe 2048-bit prime (dh_prime isn't a prime).");
                }


                /*
                * ***********************************************************************
                * Check validity of dh_prime
                * Is (dh_prime - 1) / 2 a prime?
                *
                * Almost always fails
                */
                /*
                \danog\MadelineProto\Logger::log('Executing dh_prime checks (2/3)...');
                if (!$dh_prime->subtract($one)->divide($two)[0]->isPrime()) {
                    throw new \danog\MadelineProto\Exception("dh_prime isn't a safe 2048-bit prime ((dh_prime - 1) / 2 isn't a prime).");
                }
                */

                /*
                * ***********************************************************************
                * Check validity of dh_prime
                * 2^2047 < dh_prime < 2^2048
                */
                \danog\MadelineProto\Logger::log('Executing dh_prime checks (3/3)...');
                if ($dh_prime->compare($twoe2047) <= 0 // 2^2047 < dh_prime or dh_prime > 2^2047 or ! dh_prime <= 2^2047
                    || $dh_prime->compare($twoe2048) >= 0 // dh_prime < 2^2048 or ! dh_prime >= 2^2048
                ) {
                    throw new \danog\MadelineProto\Exception("g isn't a safe 2048-bit prime (2^2047 < dh_prime < 2^2048 is false).");
                }

                /*
                * ***********************************************************************
                * Check validity of g
                * 1 < g < dh_prime - 1
                */
                \danog\MadelineProto\Logger::log('Executing g check...');

                if ($g->compare($one) <= 0 // 1 < g or g > 1 or ! g <= 1
                    || $g->compare($dh_prime->subtract($one)) >= 0 // g < dh_prime - 1 or ! g >= dh_prime - 1
                ) {
                    throw new \danog\MadelineProto\Exception('g is invalid (1 < g < dh_prime - 1 is false).');
                }

                /*
                * ***********************************************************************
                * Check validity of g_a
                * 1 < g_a < dh_prime - 1
                */
                \danog\MadelineProto\Logger::log('Executing g_a check...');
                if ($g_a->compare($one) <= 0 // 1 < g_a or g_a > 1 or ! g_a <= 1
                    || $g_a->compare($dh_prime->subtract($one)) >= 0 // g_a < dh_prime - 1 or ! g_a >= dh_prime - 1
                ) {
                    throw new \danog\MadelineProto\Exception('g_a is invalid (1 < g_a < dh_prime - 1 is false).');
                }

                foreach ($this->range(0, $this->settings['max_tries']['authorization']) as $retry_id) {
                    \danog\MadelineProto\Logger::log('Generating b...');
                    $b = new \phpseclib\Math\BigInteger(\phpseclib\Crypt\Random::string(256), 256);
                    \danog\MadelineProto\Logger::log('Generating g_b...');
                    $g_b = $g->powMod($b, $dh_prime);

                    /*
                    * ***********************************************************************
                    * Check validity of g_b
                    * 1 < g_b < dh_prime - 1
                    */
                    \danog\MadelineProto\Logger::log('Executing g_b check...');
                    if ($g_b->compare($one) <= 0 // 1 < g_b or g_b > 1 or ! g_b <= 1
                        || $g_b->compare($dh_prime->subtract($one)) >= 0 // g_b < dh_prime - 1 or ! g_b >= dh_prime - 1
                    ) {
                        throw new \danog\MadelineProto\Exception('g_b is invalid (1 < g_b < dh_prime - 1 is false).');
                    }

                    \danog\MadelineProto\Logger::log('Preparing client_DH_inner_data...');

                    $g_b_str = $g_b->toBytes();

                    /*
                    * ***********************************************************************
                    * serialize client_DH_inner_data
                    * @method client_DH_inner_data
                    * @param Server_DH_inner_data [
                    * 		int128 		$nonce 							: The value of nonce is selected randomly by the client (random number) and identifies the client within this communication
                    * 		int128		$server_nonce					: The value of server_nonce is selected randomly by the server
                    * 		long		$retry_id						: First attempt
                    * 		string		$g_b							: g^b mod dh_prime
                    * ]
                    */
                    $data = $this->tl->serialize_object(
                        ['type' => 'client_DH_inner_data'],
                        [
                            'nonce'           => $nonce,
                            'server_nonce'    => $server_nonce,
                            'retry_id'        => $retry_id,
                            'g_b'             => $g_b_str,
                        ]
                    );

                    /*
                    * ***********************************************************************
                    * encrypt client_DH_inner_data
                    */
                    $data_with_sha = sha1($data, true).$data;
                    $data_with_sha_padded = $data_with_sha.\phpseclib\Crypt\Random::string($this->posmod(-strlen($data_with_sha), 16));
                    $encrypted_data = $this->ige_encrypt($data_with_sha_padded, $tmp_aes_key, $tmp_aes_iv);

                    \danog\MadelineProto\Logger::log('Executing set_client_DH_params...');
                    /*
                    * ***********************************************************************
                    * Send set_client_DH_params query
                    * @method set_client_DH_params
                    * @param Server_DH_inner_data [
                    * 		int128 		$nonce 							: The value of nonce is selected randomly by the client (random number) and identifies the client within this communication
                    * 		int128		$server_nonce					: The value of server_nonce is selected randomly by the server
                    * 		string		$encrypted_data
                    * ]
                    * @return Set_client_DH_params_answer [
                    * 		string 		$_ 								: This value is dh_gen_ok, dh_gen_retry OR dh_gen_fail
                    * 		int128 		$server_nonce 					: The value of server_nonce is selected randomly by the server
                    * 		int128 		$new_nonce_hash1				: Return this value if server responds with dh_gen_ok
                    * 		int128 		$new_nonce_hash2				: Return this value if server responds with dh_gen_retry
                    * 		int128 		$new_nonce_hash2				: Return this value if server responds with dh_gen_fail
                    * ]
                    */
                    $Set_client_DH_params_answer = $this->method_call(
                        'set_client_DH_params',
                        [
                            'nonce'               => $nonce,
                            'server_nonce'        => $server_nonce,
                            'encrypted_data'      => $encrypted_data,
                        ]
                    );

                    /*
                    * ***********************************************************************
                    * Generate auth_key
                    */
                    \danog\MadelineProto\Logger::log('Generating authorization key...');
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
                        throw new \danog\MadelineProto\Exception('wrong nonce.');
                    }

                    /*
                    * ***********************************************************************
                    * Check if server_nonce and new server_nonce are the same
                    */
                    if ($Set_client_DH_params_answer['server_nonce'] != $server_nonce) {
                        throw new \danog\MadelineProto\Exception('wrong server nonce');
                    }

                    /*
                    * ***********************************************************************
                    * Check Set_client_DH_params_answer type
                    */
                    switch ($Set_client_DH_params_answer['_']) {
                        case 'dh_gen_ok':
                            if ($Set_client_DH_params_answer['new_nonce_hash1'] != $new_nonce_hash1) {
                                throw new \danog\MadelineProto\Exception('wrong new_nonce_hash1');
                            }

                            \danog\MadelineProto\Logger::log('Diffie Hellman key exchange processed successfully!');

                            $res_authorization['server_salt'] = \danog\PHP\Struct::unpack('<q', substr($new_nonce, 0, 8 - 0) ^ substr($server_nonce, 0, 8 - 0))[0];
                            $res_authorization['auth_key'] = $auth_key_str;
                            $res_authorization['id'] = substr($auth_key_sha, -8);

                            if ($expires_in >= 0) { //check if permanent authorization
                                $res_authorization['expires_in'] = $expires_in;
                                $res_authorization['p_q_inner_data_temp'] = $p_q_inner_data;
                            }

                            \danog\MadelineProto\Logger::log('Auth key generated');

                            return $res_authorization;
                        case 'dh_gen_retry':
                            if ($Set_client_DH_params_answer['new_nonce_hash2'] != $new_nonce_hash2) {
                                throw new \danog\MadelineProto\Exception('wrong new_nonce_hash_2');
                            }

                            //repeat foreach
                            \danog\MadelineProto\Logger::log('Retrying Auth');
                            break;
                        case 'dh_gen_fail':
                            if ($Set_client_DH_params_answer['new_nonce_hash3'] != $new_nonce_hash3) {
                                throw new \danog\MadelineProto\Exception('wrong new_nonce_hash_3');
                            }

                            \danog\MadelineProto\Logger::log('Auth Failed');
                            break 2;
                        default:
                            throw new \danog\MadelineProto\Exception('Response Error');
                            break;
                    }
                }
            } catch (\danog\MadelineProto\Exception $e) {
                \danog\MadelineProto\Logger::log('An exception occurred while generating the authorization key: '.$e->getMessage().' Retrying...');
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                \danog\MadelineProto\Logger::log('An RPCErrorException occurred while generating the authorization key: '.$e->getMessage().' Retrying...');
            }
        }

        throw new \danog\MadelineProto\Exception('Auth Failed');
    }

    public function bind_temp_auth_key($expires_in)
    {
        \danog\MadelineProto\Logger::log('Binding authorization keys...');
        $nonce = \danog\PHP\Struct::unpack('<q', \phpseclib\Crypt\Random::string(8))[0];
        $expires_at = time() + $expires_in;
        $temp_auth_key_id = \danog\PHP\Struct::unpack('<q', $this->datacenter->temp_auth_key['id'])[0];
        $perm_auth_key_id = \danog\PHP\Struct::unpack('<q', $this->datacenter->auth_key['id'])[0];
        $temp_session_id = \danog\PHP\Struct::unpack('<q', $this->datacenter->session_id)[0];
        $message_data = $this->tl->serialize_object(['type' => 'bind_auth_key_inner'],
            [
                'nonce'                       => $nonce,
                'temp_auth_key_id'            => $temp_auth_key_id,
                'perm_auth_key_id'            => $perm_auth_key_id,
                'temp_session_id'             => $temp_session_id,
                'expires_at'                  => $expires_at,
            ]
        );
        $int_message_id = $this->generate_message_id();

        $message_id = \danog\PHP\Struct::pack('<Q', $int_message_id);
        $seq_no = 0;
        $encrypted_data = \phpseclib\Crypt\Random::string(16).$message_id.\danog\PHP\Struct::pack('<II', $seq_no, strlen($message_data)).$message_data;
        $message_key = substr(sha1($encrypted_data, true), -16);
        $padding = \phpseclib\Crypt\Random::string($this->posmod(-strlen($encrypted_data), 16));
        list($aes_key, $aes_iv) = $this->aes_calculate($message_key, $this->datacenter->auth_key['auth_key']);
        $encrypted_message = $this->datacenter->auth_key['id'].$message_key.$this->ige_encrypt($encrypted_data.$padding, $aes_key, $aes_iv);

        if ($this->method_call('auth.bindTempAuthKey', ['perm_auth_key_id' => $perm_auth_key_id, 'nonce' => $nonce, 'expires_at' => $expires_at, 'encrypted_message' => $encrypted_message], $int_message_id)) {
            \danog\MadelineProto\Logger::log('Successfully binded temporary and permanent authorization keys.');

            return true;
        }
        throw new \danog\MadelineProto\Exception('An error occurred while binding temporary and permanent authorization keys.');
    }
}
