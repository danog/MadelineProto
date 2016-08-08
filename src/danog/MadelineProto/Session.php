<?php
/*
Copyright 2016 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
The PWRTelegram API is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with the MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto;

/**
 * Manages encryption and message frames.
 */
class Session extends Tools
{
    public $settings = [];

    public function __construct($settings)
    {

        // Set default settings
        $default_settings = [
            'auth_key'      => null,
            'server_salt'   => null,
            'ip_address'    => '149.154.167.50',
            'port'          => '443',
            'protocol'      => 'tcp',
            'api_id'        => 25628,
            'api_hash'      => '1fe17cda7d355166cdaa71f04122873c',
            'tl_schema'     => 'https://core.telegram.org/schema/mtproto-json',
            'rsa_key'       => '-----BEGIN RSA PUBLIC KEY-----
MIIBCgKCAQEAwVACPi9w23mF3tBkdZz+zwrzKOaaQdr01vAbU4E1pvkfj4sqDsm6
lyDONS789sVoD/xCS9Y0hkkC3gtL1tSfTlgCMOOul9lcixlEKzwKENj1Yz/s7daS
an9tqw3bfUV/nqgbhGX81v/+7RFAEd+RwFnK7a+XYl9sluzHRyVVaTTveB2GazTw
Efzk2DWgkBluml8OREmvfraX3bkHZJTKX4EQSjBbbdJ2ZXIsRrYOXfaA+xayEGB+
8hdlLmAjbCVfaigxX0CDqWeR1yFL9kwd9P0NsZRPsmoqVwMbMu7mStFai6aIhc3n
Slv8kg9qv1m6XHVQY3PnEw+QQtqSIXklHwIDAQAB
-----END RSA PUBLIC KEY-----',
            'logging'       => 1,
            'logging_param' => '/tmp/MadelineProto.log',
            'logging'       => 3,
        ];
        foreach ($default_settings as $key => $param) {
            if (!isset($settings[$key])) {
                $settings[$key] = $param;
            }
        }
        $this->settings = $settings;

        // Connect to servers
        $this->sock = new Connection($this->settings['ip_address'], $this->settings['port'], $this->settings['protocol']);

        // Load rsa key
        $this->key = new RSA($settings['rsa_key']);
        // Istantiate struct class
        $this->struct = new \danog\PHP\Struct();
        // Istantiate prime class
        $this->PrimeModule = new PrimeModule();
        // Istantiate TL class
        try {
            $this->tl = new TL\TL($this->settings['tl_schema']);
        } catch (Exception $e) {
            $this->tl = new TL\TL(__DIR__.'/TL_schema.JSON');
        }
        // Istantiate logging class
        $this->log = new Logging($this->settings['logging'], $this->settings['logging_param']);
        // Set some defaults
        $this->auth_key = $this->settings['auth_key'];
        $this->number = 0;
        $this->timedelta = 0;
        $this->session_id = \phpseclib\Crypt\Random::string(8);
        if (isset($this->settings['auth_key'])) {
            $this->auth_key = $this->settings['auth_key'];
        }
        $this->auth_key_id = $this->auth_key ? substr(sha1($this->auth_key, true), -8) : null;
        $this->MAX_RETRY = 5;
        $this->AUTH_MAX_RETRY = 5;
    }

    public function __destruct()
    {
        unset($this->sock);
    }

    /**
     * Function to get hex crc32.
     *
     * @param $data Data to encode.
     */
    public function newcrc32($data)
    {
        return hexdec(hash('crc32b', $data));
    }

    /**
     * Forming the message frame and sending message to server
     * :param message: byte string to send.
     */
    public function send_message($message_data)
    {
        $message_id = $this->struct->pack('<Q', (int) ((time() + $this->timedelta) * pow(2, 30)) * 4);

        if (($this->auth_key == null) || ($this->server_salt == null)) {
            $message = Tools::string2bin('\x00\x00\x00\x00\x00\x00\x00\x00').$message_id.$this->struct->pack('<I', strlen($message_data)).$message_data;
        } else {
            $encrypted_data =
                $this->server_salt.$this->session_id.$message_id.$this->struct->pack('<II', $this->number, strlen($message_data)).$message_data;
            $message_key = substr(sha1($encrypted_data, true), -16);
            $padding = \phpseclib\Crypt\Random::string(posmod(-strlen($encrypted_data), 16));
            $this->log->log(strlen($encrypted_data.$padding));
            list($aes_key, $aes_iv) = $this->aes_calculate($message_key);
            $message = $this->auth_key_id.$message_key.crypt::ige_encrypt($encrypted_data.$padding, $aes_key, $aes_iv);
        }
        $step1 = $this->struct->pack('<II', (strlen($message) + 12), $this->number).$message;
        $step2 = $step1.$this->struct->pack('<I', $this->newcrc32($step1));
        $this->sock->write($step2);
        $this->number += 1;
    }

    /**
     * Reading socket and receiving message from server. Check the CRC32.
     */
    public function recv_message()
    {
        $packet_length_data = $this->sock->read(4);
        if (strlen($packet_length_data) < 4) {
            throw new Exception('Nothing in the socket!');
        }
        $packet_length = $this->struct->unpack('<I', $packet_length_data)[0];
        $packet = $this->sock->read($packet_length - 4);
        if (!($this->newcrc32($packet_length_data.substr($packet, 0, -4)) == $this->struct->unpack('<I', substr($packet, -4))[0])) {
            throw new Exception('CRC32 was not correct!');
        }
        $x = $this->struct->unpack('<I', substr($packet, 0, 4));
        $auth_key_id = substr($packet, 4, 8);
        if ($auth_key_id == Tools::string2bin('\x00\x00\x00\x00\x00\x00\x00\x00')) {
            list($message_id, $message_length) = $this->struct->unpack('<8sI', substr($packet, 12, 12));
            $data = substr($packet, 24, (24 + $message_length) - 24);
        } elseif ($auth_key_id == $this->auth_key_id) {
            $message_key = substr($packet, 12, 28 - 12);
            $encrypted_data = substr($packet, 28, -4 - 28);
            list($aes_key, $aes_iv) = $this->aes_calculate($message_key, 'from server');
            $decrypted_data = crypt::ige_decrypt($encrypted_data, $aes_key, $aes_iv);
            if (substr($decrypted_data, 0, 8) != $this->server_salt) {
                throw new Exception('Server salt does not match.');
            }
            if (substr($decrypted_data, 8, 8) != $this->session_id) {
                throw new Exception('Session id does not match.');
            }
            $message_id = substr($decrypted_data, 16, 24 - 16);
            $seq_no = $this->struct->unpack('<I', substr($decrypted_data, 24, 28 - 24)) [0];
            $message_data_length = $this->struct->unpack('<I', substr($decrypted_data, 28, 32 - 28)) [0];
            $data = substr($decrypted_data, 32, (32 + $message_data_length) - 32);
        } else {
            throw new Exception('Got unknown auth_key id');
        }

        return $data;
    }

    public function method_call($method, $kwargs)
    {
        //var_dump($kwargs);
        foreach (range(1, $this->MAX_RETRY) as $i) {
            try {
                $this->send_message($this->tl->serialize_method($method, $kwargs));
                $server_answer = $this->recv_message();
            } catch (Exception $e) {
                $this->log->log(PHP_EOL.'An error occurred while calling method '.$method.': '.$e->getMessage().PHP_EOL.'Stack trace:'.$e->getTraceAsString().PHP_EOL.'Retrying to call method...'.PHP_EOL);
                continue;
            }
            if ($server_answer == null) {
                throw new Exception('An error occurred while calling method '.$method.'.');
            }

            return $this->tl->deserialize(Tools::fopen_and_write('php://memory', 'rw+b', $server_answer));
        }
        throw new Exception('An error occurred while calling method '.$method.'.');
    }

    public function create_auth_key()
    {

        // Make pq request
        $nonce = \phpseclib\Crypt\Random::string(16);
        $this->log->log('Handshake: Requesting pq');
        $ResPQ = $this->method_call('req_pq', ['nonce' => $nonce]);
        $server_nonce = $ResPQ['server_nonce'];
        if ($ResPQ['nonce'] !== $nonce) {
            throw new Exception('Handshake: wrong nonce');
        }
        $pq_bytes = $ResPQ['pq'];
        foreach ($ResPQ['server_public_key_fingerprints'] as $curfp) {
            if ($curfp === $this->key->fp_float) {
                $public_key_fingerprint = $curfp;
                break;
            }
        }
        if (!isset($public_key_fingerprint)) {
            throw new Exception("Handshake: couldn't find our key in the server_public_key_fingerprints vector.");
        }

        // Compute p and q
        $pq = new \phpseclib\Math\BigInteger($pq_bytes, 256);
        list($p, $q) = $this->PrimeModule->primefactors($pq);
        $p = new \phpseclib\Math\BigInteger($p);
        $q = new \phpseclib\Math\BigInteger($q);
        if ($p->compare($q) > 0) {
            list($p, $q) = [$q, $p];
        }
        if (!(($pq->equals($p->multiply($q))) && ($p < $q))) {
            throw new Exception("Handshake: couldn't compute p or q.");
        }


        $this->log->log(sprintf('Factorization %s = %s * %s', $pq, $p, $q));

        $p_bytes = $this->struct->pack('>Q', (string) $p);
        $q_bytes = $this->struct->pack('>Q', (string) $q);
        $new_nonce = \phpseclib\Crypt\Random::string(32);
        $data = $this->tl->serialize_obj('p_q_inner_data', ['pq' => $pq_bytes, 'p' => $p_bytes, 'q' => $q_bytes, 'nonce' => $nonce, 'server_nonce' => $server_nonce, 'new_nonce' => $new_nonce]);
        $sha_digest = sha1($data, true);
        $random_bytes = \phpseclib\Crypt\Random::string(255 - strlen($data) - strlen($sha_digest));
        $to_encrypt = $sha_digest.$data.$random_bytes;
        $encrypted_data = $this->key->encrypt($to_encrypt);
        $this->log->log('Starting Diffie Hellman key exchange');
        $server_dh_params = $this->method_call('req_DH_params', ['nonce' => $nonce, 'server_nonce' => $server_nonce, 'p' => $p_bytes, 'q' => $q_bytes, 'public_key_fingerprint' => $public_key_fingerprint, 'encrypted_data' => $encrypted_data]);
        if ($nonce != $server_dh_params['nonce']) {
            throw new Exception('Handshake: wrong nonce.');
        }
        if ($server_nonce != $server_dh_params['server_nonce']) {
            throw new Exception('Handshake: wrong server nonce.');
        }
        $encrypted_answer = $server_dh_params['encrypted_answer'];
        $tmp_aes_key = sha1($new_nonce.$server_nonce, true).substr(sha1($server_nonce.$new_nonce, true), 0, 12);
        $tmp_aes_iv = substr(sha1($server_nonce.$new_nonce, true), 12, 8).sha1($new_nonce.$new_nonce, true).substr($new_nonce, 0, 4);
        $answer_with_hash = crypt::ige_decrypt($encrypted_answer, $tmp_aes_key, $tmp_aes_iv);
        $answer_hash = substr($answer_with_hash, 0, 20);
        $answer = substr($answer_with_hash, 20);
        $server_DH_inner_data = deserialize(Tools::fopen_and_write('php://memory', 'rw+b', $answer));
        if ($nonce != $server_DH_inner_data['nonce']) {
            throw new Exception('Handshake: wrong nonce');
        }
        if ($server_nonce != $server_DH_inner_data['server_nonce']) {
            throw new Exception('Handshake: wrong server nonce');
        }
        $dh_prime_str = $server_DH_inner_data['dh_prime'];
        $g = $server_DH_inner_data['g'];
        $g_a_str = $server_DH_inner_data['g_a'];
        $server_time = $server_DH_inner_data['server_time'];
        $this->timedelta = ($server_time - time());
        $this->log->log(sprintf('Server-client time delta = %.1f s', $this->timedelta));
        $dh_prime = $this->struct->unpack('>Q', $dh_prime_str);
        $g_a = $this->struct->unpack('>Q', $g_a_str);
        if (!$this->PrimeModule->isprime($dh_prime)) {
            throw new Exception("Handshake: dh_prime isn't a prime.");
        }
        $retry_id = 0;
        $b_str = \phpseclib\Crypt\Random::string(256);
        $b = $this->struct->unpack('>Q', $b_str);
        $g_b = pow($g, $b, $dh_prime);
        $g_b_str = $this->struct->pack('>Q', $g_b);
        $data = serialize_obj(['client_DH_inner_data'], ['nonce' => $nonce, 'server_nonce' => $server_nonce, 'retry_id' => $retry_id, 'g_b' => $g_b_str]);
        $data_with_sha = sha1($data, true).$data;
        $data_with_sha_padded = $data_with_sha.\phpseclib\Crypt\Random::string(posmod(-strlen($data_with_sha), 16));
        $encrypted_data = crypt::ige_encrypt($data_with_sha_padded, $tmp_aes_key, $tmp_aes_iv);
        foreach (pyjslib_range(1, $this->AUTH_MAX_RETRY) as $i) {
            $Set_client_DH_params_answer = $this->method_call('set_client_DH_params', ['nonce' => $nonce, 'server_nonce' => $server_nonce, 'encrypted_data' => $encrypted_data]);
            $auth_key = pow($g_a, $b, $dh_prime);
            $auth_key_str = $this->struct->pack('>Q', $auth_key);
            $auth_key_sha = sha1($auth_key_str, true);
            $auth_key_aux_hash = substr($auth_key_sha, 0, 8);
            $new_nonce_hash1 = substr(sha1($new_nonce.''.$auth_key_aux_hash, true), -16);
            $new_nonce_hash2 = substr(sha1($new_nonce.''.$auth_key_aux_hash, true), -16);
            $new_nonce_hash3 = substr(sha1($new_nonce.''.$auth_key_aux_hash, true), -16);
            if ($Set_client_DH_params_answer['nonce'] != $nonce) {
                throw new Exception('Handshake: wrong nonce.');
            }
            if ($Set_client_DH_params_answer['server_nonce'] != $server_nonce) {
                throw new Exception('Handshake: wrong server nonce');
            }
            if ($Set_client_DH_params_answer->name == 'dh_gen_ok') {
                if ($Set_client_DH_params_answer['new_nonce_hash1'] != $new_nonce_hash1) {
                    throw new Exception('Handshake: wrong new_nonce_hash1');
                }
                $this->log->log('Diffie Hellman key exchange processed successfully');
                $this->server_salt = new strxor(substr($new_nonce, 0, 8 - 0), substr($server_nonce, 0, 8 - 0));
                $this->auth_key = $auth_key_str;
                $this->auth_key_id = substr($auth_key_sha, -8);
                $this->log->log('Auth key generated');

                return 'Auth Ok';
            } elseif ($Set_client_DH_params_answer->name == 'dh_gen_retry') {
                if ($Set_client_DH_params_answer['new_nonce_hash2'] != $new_nonce_hash2) {
                    throw new Exception('Handshake: wrong new_nonce_hash_2');
                }
                $this->log->log('Retry Auth');
            } elseif ($Set_client_DH_params_answer->name == 'dh_gen_fail') {
                if ($Set_client_DH_params_answer['new_nonce_hash3'] != $new_nonce_hash3) {
                    throw new Exception('Handshake: wrong new_nonce_hash_3');
                }
                $this->log->log('Auth Failed');
                throw new Exception('Auth Failed');
            } else {
                throw new Exception('Response Error');
            }
        }
    }

    public function aes_calculate($msg_key, $direction = 'to server')
    {
        $x = ($direction == 'to server') ? 0 : 8;
        $sha1_a = sha1($msg_key.substr($this->auth_key, $x, ($x + 32) - $x), true);
        $sha1_b = sha1(substr($this->auth_key, ($x + 32), ($x + 48) - ($x + 32)).$msg_key.substr($this->auth_key, (48 + $x), (64 + $x) - (48 + $x)), true);
        $sha1_c = sha1(substr($this->auth_key, ($x + 64), ($x + 96) - ($x + 64)).$msg_key, true);
        $sha1_d = sha1($msg_key.substr($this->auth_key, ($x + 96), ($x + 128) - ($x + 96)), true);
        $aes_key = substr($sha1_a, 0, 8 - 0).substr($sha1_b, 8, 20 - 8).substr($sha1_c, 4, 16 - 4);
        $aes_iv = substr($sha1_a, 8, 20 - 8).substr($sha1_b, 0, 8 - 0).substr($sha1_c, 16, 20 - 16).substr($sha1_d, 0, 8 - 0);

        return [$aes_key, $aes_iv];
    }
}
