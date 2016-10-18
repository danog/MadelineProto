<?php
/*
Copyright 2016 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with the MadelineProto.
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
            $this->log->log('Requesting pq');

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
                throw new Exception('wrong nonce');
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
                throw new Exception("couldn't find our key in the server_public_key_fingerprints vector.");
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
                throw new Exception("couldn't compute p and q.");
            }

            $this->log->log('Factorization '.$pq.' = '.$p.' * '.$q);

            /*
             * ***********************************************************************
             * Serialize object for req_DH_params
             */
            $p_bytes = $this->struct->pack('>I', (string) $p);
            $q_bytes = $this->struct->pack('>I', (string) $q);

            $new_nonce = \phpseclib\Crypt\Random::string(32);
            if ($expires_in < 0) {
                $data = $this->tl->serialize_obj('p_q_inner_data',
                    [
                        'pq'              => $pq_bytes,
                        'p'               => $p_bytes,
                        'q'               => $q_bytes,
                        'nonce'           => $nonce,
                        'server_nonce'    => $server_nonce,
                        'new_nonce'       => $new_nonce,
                    ]
                );
            } else {
                $data = $this->tl->serialize_obj('p_q_inner_data_temp',
                    [
                        'pq'              => $pq_bytes,
                        'p'               => $p_bytes,
                        'q'               => $q_bytes,
                        'nonce'           => $nonce,
                        'server_nonce'    => $server_nonce,
                        'new_nonce'       => $new_nonce,
                        'expires_in'      => $expires_in,
                    ]
                );
            }

            /*
             * ***********************************************************************
             * Encrypt serialized object
             */
            $sha_digest = sha1($data, true);
            $random_bytes = \phpseclib\Crypt\Random::string(255 - strlen($data) - strlen($sha_digest));
            $to_encrypt = $sha_digest.$data.$random_bytes;
            $encrypted_data = $this->key->encrypt($to_encrypt);

            $this->log->log('Starting Diffie Hellman key exchange');
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
             * 		int128 		$nonce 							: The value of nonce is selected randomly by the server
             * 		int128 		$server_nonce 					: The value of server_nonce is selected randomly by the server
             * 		string 		$new_nonce_hash					: Return this value If server responds is server_DH_params_fail
             * 		string 		$encrypted_answer				: Return this value If server responds is server_DH_params_ok
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
                throw new Exception('wrong nonce.');
            }

            /*
             * ***********************************************************************
             * Check if server_nonce and new server_nonce are the same
             */
            if ($server_nonce != $server_dh_params['server_nonce']) {
                throw new Exception('wrong server nonce.');
            }

            /*
             * ***********************************************************************
             * Check valid new nonce hash if return from server
             * new nonce hash return in server_DH_params_fail
             */
            if (isset($server_dh_params['new_nonce_hash']) && substr(sha1($new_nonce), -32) != $server_dh_params['new_nonce_hash']) {
                throw new Exception('wrong new nonce hash.');
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
                throw new Exception('answer_hash mismatch.');
            }

            if ($nonce != $server_DH_inner_data['nonce']) {
                throw new Exception('wrong nonce');
            }

            if ($server_nonce != $server_DH_inner_data['server_nonce']) {
                throw new Exception('wrong server nonce');
            }

            $g = new \phpseclib\Math\BigInteger($server_DH_inner_data['g']);
            $g_a = new \phpseclib\Math\BigInteger($server_DH_inner_data['g_a'], 256);
            $dh_prime = new \phpseclib\Math\BigInteger($server_DH_inner_data['dh_prime'], 256);

            /*
             * ***********************************************************************
             * Time delta
             */
            $server_time = $server_DH_inner_data['server_time'];
            $this->timedelta = $server_time - time();

            $this->log->log(sprintf('Server-client time delta = %.1f s', $this->timedelta));


            /*
             * ***********************************************************************
             * Define some needed numbers for BigInteger
             */
            $this->log->log('Executing dh_prime checks...');
            $one = new \phpseclib\Math\BigInteger(1);
            $two = new \phpseclib\Math\BigInteger(2);
            $twoe2047 = new \phpseclib\Math\BigInteger('16158503035655503650357438344334975980222051334857742016065172713762327569433945446598600705761456731844358980460949009747059779575245460547544076193224141560315438683650498045875098875194826053398028819192033784138396109321309878080919047169238085235290822926018152521443787945770532904303776199561965192760957166694834171210342487393282284747428088017663161029038902829665513096354230157075129296432088558362971801859230928678799175576150822952201848806616643615613562842355410104862578550863465661734839271290328348967522998634176499319107762583194718667771801067716614802322659239302476074096777926805529798115328');
            $twoe2048 = new \phpseclib\Math\BigInteger('32317006071311007300714876688669951960444102669715484032130345427524655138867890893197201411522913463688717960921898019494119559150490921095088152386448283120630877367300996091750197750389652106796057638384067568276792218642619756161838094338476170470581645852036305042887575891541065808607552399123930385521914333389668342420684974786564569494856176035326322058077805659331026192708460314150258592864177116725943603718461857357598351152301645904403697613233287231227125684710820209725157101726931323469678542580656697935045997268352998638215525166389437335543602135433229604645318478604952148193555853611059596230656');

            /*
             * ***********************************************************************
             * Check validity of dh_prime
             * 2^2047 < dh_prime < 2^2048
             */
            if (!$dh_prime->isPrime()) {
                throw new Exception("dh_prime isn't a safe 2048-bit prime (dh_prime isn't a prime).");
            }

            /*
            // Almost always fails
            if (!$dh_prime->subtract($one)->divide($two)[0]->isPrime()) {
                throw new Exception("dh_prime isn't a safe 2048-bit prime ((dh_prime - 1) / 2 isn't a prime).");
            }
            */

            if ($dh_prime->compare($twoe2047) <= 0 // 2^2047 < dh_prime or dh_prime > 2^2047 or ! dh_prime <= 2^2047
            || $dh_prime->compare($twoe2048) >= 0 // dh_prime < 2^2048 or ! dh_prime >= 2^2048
            ) {
                throw new Exception("g isn't a safe 2048-bit prime (2^2047 < dh_prime < 2^2048 is false).");
            }

            /*
             * ***********************************************************************
             * Check validity of g
             * 1 < g < dh_prime - 1
             */
            if ($g->compare($one) <= 0 // 1 < g or g > 1 or ! g <= 1
            || $g->compare($dh_prime->subtract($one)) >= 0 // g < dh_prime - 1 or ! g >= dh_prime - 1
            ) {
                throw new Exception('g is invalid (1 < g < dh_prime - 1 is false).');
            }

            /*
             * ***********************************************************************
             * Check validity of g_a
             * 1 < g_a < dh_prime - 1
             */
            if ($g_a->compare($one) <= 0 // 1 < g_a or g_a > 1 or ! g_a <= 1
            || $g_a->compare($dh_prime->subtract($one)) >= 0 // g_a < dh_prime - 1 or ! g_a >= dh_prime - 1
            ) {
                throw new Exception('g_a is invalid (1 < g_a < dh_prime - 1 is false).');
            }

            foreach ($this->range(0, $this->settings['max_tries']['authorization']) as $retry_id) {
                $b = new \phpseclib\Math\BigInteger(\phpseclib\Crypt\Random::string(256), 256);
                $g_b = $g->powMod($b, $dh_prime);

                /*
                 * ***********************************************************************
                 * Check validity of g_b
                 * 1 < g_b < dh_prime - 1
                 */
                if ($g_b->compare($one) <= 0 // 1 < g_b or g_b > 1 or ! g_b <= 1
                || $g_b->compare($dh_prime->subtract($one)) >= 0 // g_b < dh_prime - 1 or ! g_b >= dh_prime - 1
                ) {
                    throw new Exception('g_b is invalid (1 < g_b < dh_prime - 1 is false).');
                }

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
                $data = $this->tl->serialize_obj('client_DH_inner_data',
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
                 * 		int128 		$new_nonce_hash1				: Return this value If server responds is dh_gen_ok
                 * 		int128 		$new_nonce_hash2				: Return this value If server responds is dh_gen_retry
                 * 		int128 		$new_nonce_hash2				: Return this value If server responds is dh_gen_fail
                 * ]
                 */
                $Set_client_DH_params_answer = $this->method_call('set_client_DH_params',
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
                    throw new Exception('wrong nonce.');
                }

                /*
                 * ***********************************************************************
                 * Check if server_nonce and new server_nonce are the same
                 */
                if ($Set_client_DH_params_answer['server_nonce'] != $server_nonce) {
                    throw new Exception('wrong server nonce');
                }

                /*
                 * ***********************************************************************
                 * Check Set_client_DH_params_answer type
                 */
                switch ($Set_client_DH_params_answer['_']) {
                    case 'dh_gen_ok':
                        if ($Set_client_DH_params_answer['new_nonce_hash1'] != $new_nonce_hash1) {
                            throw new Exception('wrong new_nonce_hash1');
                        }

                        $this->log->log('Diffie Hellman key exchange processed successfully');

                        $res_authorization['server_salt'] = $this->struct->unpack('<q', substr($new_nonce, 0, 8 - 0) ^ substr($server_nonce, 0, 8 - 0))[0];
                        $res_authorization['auth_key'] = $auth_key_str;
                        $res_authorization['id'] = substr($auth_key_sha, -8);

                        if ($expires_in < 0) { //check if permanent authorization
                            $res_authorization['expires_in'] = $expires_in;
                        }

                        $this->log->log('Auth key generated');
                        $this->timedelta = 0;

                        return $res_authorization;
                    case 'dh_gen_retry':
                        if ($Set_client_DH_params_answer['new_nonce_hash2'] != $new_nonce_hash2) {
                            throw new Exception('wrong new_nonce_hash_2');
                        }

                        //repeat foreach
                        $this->log->log('Retrying Auth');
                        break;
                    case 'dh_gen_fail':
                        if ($Set_client_DH_params_answer['new_nonce_hash3'] != $new_nonce_hash3) {
                            throw new Exception('wrong new_nonce_hash_3');
                        }

                        $this->log->log('Auth Failed');
                        break 2;
                    default:
                        throw new Exception('Response Error');
                        break;
                }
            }
        }

        throw new Exception('Auth Failed');
    }

    public function bind_temp_auth_key($expires_in)
    {
        $nonce = $this->struct->unpack('<q', \phpseclib\Crypt\Random::string(8))[0];
        $expires_at = time() + $expires_in;
        $temp_auth_key_id = $this->struct->unpack('<q', $this->settings['authorization']['temp_auth_key']['id'])[0];
        $perm_auth_key_id = $this->struct->unpack('<q', $this->settings['authorization']['auth_key']['id'])[0];
        $temp_session_id = $this->struct->unpack('<q', $this->settings['authorization']['session_id'])[0];
        $message_data = $this->tl->serialize_obj('bind_auth_key_inner',
            [
                'nonce'                       => $nonce,
                'temp_auth_key_id'            => $temp_auth_key_id,
                'perm_auth_key_id'            => $perm_auth_key_id,
                'temp_session_id'             => $temp_session_id,
                'expires_at'                  => $expires_at,
            ]
        );
        $int_message_id = $this->generate_message_id();
        $message_id = $this->struct->pack('<Q', $int_message_id);
        $seq_no = 0;
        $encrypted_data = \phpseclib\Crypt\Random::string(16).$message_id.$this->struct->pack('<II', $seq_no, strlen($message_data)).$message_data;
        $message_key = substr(sha1($encrypted_data, true), -16);
        $padding = \phpseclib\Crypt\Random::string($this->posmod(-strlen($encrypted_data), 16));
        list($aes_key, $aes_iv) = $this->aes_calculate($message_key, $this->settings['authorization']['auth_key']['auth_key']);
        $encrypted_message = $this->settings['authorization']['auth_key']['id'].$message_key.$this->ige_encrypt($encrypted_data.$padding, $aes_key, $aes_iv);

        if ($this->method_call('auth.bindTempAuthKey', ['perm_auth_key_id' => $perm_auth_key_id, 'nonce' => $nonce, 'expires_at' => $expires_at, 'encrypted_message' => $encrypted_message], $message_id)) {
            $this->log->log('Successfully binded temporary and permanent authorization keys.');
            $this->write_client_info();

            return true;
        }
        throw new Exception('An error occurred while binding temporary and permanent authorization keys.');
    }
}
