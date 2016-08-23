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

namespace danog\MadelineProto;

/**
 * Manages encryption and message frames.
 */
class Session extends Tools
{
    public $settings = [];

    public function __construct($settings = [])
    {
        // Set default settings
        $default_settings = [
            'authorization' => [
                'auth_key'                         => null,
                'temp_auth_key'                    => null,
                'default_temp_auth_key_expires_in' => 86400,
                'session_id'                       => \phpseclib\Crypt\Random::string(8),
                'rsa_key'                          => '-----BEGIN RSA PUBLIC KEY-----
MIIBCgKCAQEAwVACPi9w23mF3tBkdZz+zwrzKOaaQdr01vAbU4E1pvkfj4sqDsm6
lyDONS789sVoD/xCS9Y0hkkC3gtL1tSfTlgCMOOul9lcixlEKzwKENj1Yz/s7daS
an9tqw3bfUV/nqgbhGX81v/+7RFAEd+RwFnK7a+XYl9sluzHRyVVaTTveB2GazTw
Efzk2DWgkBluml8OREmvfraX3bkHZJTKX4EQSjBbbdJ2ZXIsRrYOXfaA+xayEGB+
8hdlLmAjbCVfaigxX0CDqWeR1yFL9kwd9P0NsZRPsmoqVwMbMu7mStFai6aIhc3n
Slv8kg9qv1m6XHVQY3PnEw+QQtqSIXklHwIDAQAB
-----END RSA PUBLIC KEY-----',
                'message_ids_limit' => 5,
            ],
            'connection' => [
                'ip_address'    => '149.154.167.50',
                'port'          => '443',
                'protocol'      => 'tcp_full',
            ],
            'app_info' => [
                'api_id'        => 25628,
                'api_hash'      => '1fe17cda7d355166cdaa71f04122873c',
            ],
            'tl_schema'     => [
                'layer'         => 55,
                'src'           => [
                    __DIR__.'/TL_mtproto_v1.json',
                    __DIR__.'/TL_telegram_v55.json',
                ],
            ],
            'logging'       => [
                'logging'       => 1,
                'logging_param' => '/tmp/MadelineProto.log',
                'logging'       => 3,
            ],
            'max_tries'         => [
                'query'         => 5,
                'authorization' => 5,
            ],
        ];
        foreach ($default_settings as $key => $param) {
            if (!isset($settings[$key])) {
                $settings[$key] = $param;
            }
            foreach ($param as $subkey => $subparam) {
                if (!isset($settings[$key][$subkey])) {
                    $settings[$key][$subkey] = $subparam;
                }
            }
        }
        $this->settings = $settings;

        // Connect to servers
        $this->sock = new Connection($this->settings['connection']['ip_address'], $this->settings['connection']['port'], $this->settings['connection']['protocol']);

        // Load rsa key
        $this->key = new RSA($settings['authorization']['rsa_key']);
        // Istantiate struct class
        $this->struct = new \danog\PHP\StructTools();
        // Istantiate prime class
        $this->PrimeModule = new PrimeModule();
        // Istantiate TL class
        $this->tl = new TL\TL($this->settings['tl_schema']['src']);
        // Istantiate logging class
        $this->log = new Logging($this->settings['logging']['logging'], $this->settings['logging']['logging_param']);

        $this->seq_no = 0;
        $this->timedelta = 0; // time delta
        $this->incoming_message_ids = [];
        $this->outgoing_message_ids = [];
        $this->ack_incoming_message_ids = [];
        $this->ack_outgoing_message_ids = [];
        $this->future_salts = [];

        if ($this->settings['authorization']['temp_auth_key'] == null || $this->settings['authorization']['auth_key'] == null) {
            if ($this->settings['authorization']['auth_key'] == null) {
                $this->settings['authorization']['auth_key'] = $this->create_auth_key(-1);
            }
            $this->settings['authorization']['temp_auth_key'] = $this->create_auth_key($this->settings['authorization']['default_temp_auth_key_expires_in']);
        }
    }

    public function __destruct()
    {
        unset($this->sock);
    }

    public function check_message_id($new_message_id, $outgoing)
    {
        if (((int) ((time() + $this->timedelta - 300) * pow(2, 30)) * 4) > $new_message_id) {
            throw new Exception('Given message id ('.$new_message_id.') is too old.');
        }
        if (((int) ((time() + $this->timedelta + 30) * pow(2, 30)) * 4) < $new_message_id) {
            throw new Exception('Given message id ('.$new_message_id.') is too new.');
        }
        if ($outgoing) {
            if ($new_message_id % 4 != 0) {
                throw new Exception('Given message id ('.$new_message_id.') is not divisible by 4.');
            }
            $this->outgoing_message_ids[] = $new_message_id;
            if (count($this->outgoing_message_ids) > $this->settings['authorization']['message_ids_limit']) {
                array_shift($this->outgoing_message_ids);
            }
        } else {
            if ($new_message_id % 4 != 1 && $new_message_id % 4 != 3) {
                throw new Exception('message id mod 4 != 1 or 3');
            }
            foreach ($this->incoming_message_ids as $message_id) {
                if ($new_message_id <= $message_id) {
                    throw new Exception('Given message id ('.$new_message_id.') is lower than or equal than the current limit ('.$message_id.').');
                }
            }
            $this->incoming_message_ids[] = $new_message_id;
            if (count($this->incoming_message_ids) > $this->settings['authorization']['message_ids_limit']) {
                array_shift($this->incoming_message_ids);
            }
        }
    }

    public function ack_outgoing_message_id($message_id)
    {
        // The server acknowledges that it received my message
        if (!in_array($message_id, $this->outgoing_message_ids)) {
            throw new Exception("Couldn't find message id ".$message_id.' in the array of outgoing message ids. Maybe try to increase its size?');
        }
        $this->ack_outgoing_message_ids[] = $message_id;
        if (count($this->ack_outgoing_message_ids) > $this->settings['authorization']['message_ids_limit']) {
            array_shift($this->ack_outgoing_message_ids);
        }
    }

    public function ack_incoming_message_id($message_id)
    {
        if ($this->settings['authorization']['temp_auth_key']['id'] === null || $this->settings['authorization']['temp_auth_key']['id'] == Tools::string2bin('\x00\x00\x00\x00\x00\x00\x00\x00')) {
            return;
        }
        // I let the server know that I received its message
        if (!in_array($message_id, $this->incoming_message_ids)) {
            throw new Exception("Couldn't find message id ".$message_id.' in the array of incoming message ids. Maybe try to increase its size?');
        }
        $this->object_call('msgs_ack', ['msg_ids' => [$message_id]]);
        $this->ack_incoming_message_ids[] = $message_id;
        if (count($this->ack_incoming_message_ids) > $this->settings['authorization']['message_ids_limit']) {
            array_shift($this->ack_incoming_message_ids);
        }
    }

    public function generate_seq_no($content_related = true)
    {
        $in = $content_related ? 1 : 0;
        $value = $this->seq_no;
        $this->seq_no += $in;
        var_dump((($value * 2) + $in));

        return ($value * 2) + $in;
    }

    /**
     * Forming the message frame and sending message to server
     * :param message: byte string to send.
     */
    public function send_message($message_data, $content_related)
    {
        $int_message_id = (int) ((time() + $this->timedelta) * pow(2, 30)) * 4;
        $message_id = $this->struct->pack('<Q', $int_message_id);
        $this->check_message_id($int_message_id, true);
        if (($this->settings['authorization']['temp_auth_key']['auth_key'] == null) || ($this->settings['authorization']['temp_auth_key']['server_salt'] == null)) {
            $message = Tools::string2bin('\x00\x00\x00\x00\x00\x00\x00\x00').$message_id.$this->struct->pack('<I', strlen($message_data)).$message_data;
            $this->last_sent = ['message_id' => $int_message_id];
        } else {
            $seq_no = $this->generate_seq_no($content_related);
            $encrypted_data = $this->settings['authorization']['temp_auth_key']['server_salt'].$this->settings['authorization']['session_id'].$message_id.$this->struct->pack('<II', $seq_no, strlen($message_data)).$message_data;
            $message_key = substr(sha1($encrypted_data, true), -16);
            $padding = \phpseclib\Crypt\Random::string(Tools::posmod(-strlen($encrypted_data), 16));
            list($aes_key, $aes_iv) = $this->aes_calculate($message_key);
            $message = $this->settings['authorization']['temp_auth_key']['id'].$message_key.Crypt::ige_encrypt($encrypted_data.$padding, $aes_key, $aes_iv);
            $this->last_sent = ['message_id' => $int_message_id, 'seq_no' => $seq_no];
        }
        $this->sock->send_message($message);
    }

    /**
     * Reading socket and receiving message from server. Check the CRC32.
     */
    public function recv_message()
    {
        $payload = $this->sock->read_message();
        if (fstat($payload)['size'] == 4) {
            throw new Exception('Server response error: '.abs($this->struct->unpack('<i', fread($payload, 4))[0]));
        }
        $auth_key_id = fread($payload, 8);
        if ($auth_key_id == Tools::string2bin('\x00\x00\x00\x00\x00\x00\x00\x00')) {
            list($message_id, $message_length) = $this->struct->unpack('<QI', fread($payload, 12));
            $this->check_message_id($message_id, false);
            $message_data = fread($payload, $message_length);
            $this->last_received = ['message_id' => $message_id];
        } elseif ($auth_key_id == $this->settings['authorization']['temp_auth_key']['id']) {
            $message_key = fread($payload, 16);
            $encrypted_data = stream_get_contents($payload);
            list($aes_key, $aes_iv) = $this->aes_calculate($message_key, 'from server');
            $decrypted_data = Crypt::ige_decrypt($encrypted_data, $aes_key, $aes_iv);

            $server_salt = substr($decrypted_data, 0, 8);
            if ($server_salt != $this->settings['authorization']['temp_auth_key']['server_salt']) {
                throw new Exception('Server salt mismatch.');
            }

            $session_id = substr($decrypted_data, 8, 8);
            if ($session_id != $this->settings['authorization']['session_id']) {
                throw new Exception('Session id mismatch.');
            }

            $message_id = $this->struct->unpack('<Q', substr($decrypted_data, 16, 8))[0];
            $this->check_message_id($message_id, false);

            $seq_no = $this->struct->unpack('<I', substr($decrypted_data, 24, 4)) [0];
            // Dunno how to handle any incorrect sequence numbers

            $message_data_length = $this->struct->unpack('<I', substr($decrypted_data, 28, 4)) [0];

            if ($message_data_length > strlen($decrypted_data)) {
                throw new Exception('message_data_length is too big');
            }

            if ((strlen($decrypted_data) - 32) - $message_data_length > 15) {
                throw new Exception('difference between message_data_length and the length of the remaining decrypted buffer is too big');
            }

            if ($message_data_length < 0) {
                throw new Exception('message_data_length not positive');
            }

            if ($message_data_length % 4 != 0) {
                throw new Exception('message_data_length not divisible by 4');
            }

            $message_data = substr($decrypted_data, 32, $message_data_length);
            if ($message_key != substr(sha1(substr($decrypted_data, 0, 32 + $message_data_length), true), -16)) {
                throw new Exception('msg_key mismatch');
            }
            $this->last_received = ['message_id' => $message_id, 'seq_no' => $seq_no];
        } else {
            throw new Exception('Got unknown auth_key id');
        }

        return $message_data;
    }

    public function method_call($method, $args)
    {
        foreach (range(1, $this->settings['max_tries']['query']) as $i) {
            try {
                $this->send_message($this->tl->serialize_method($method, $args), $this->tl->content_related($method));
                $server_answer = $this->recv_message();
            } catch (Exception $e) {
                $this->log->log('An error occurred while calling method '.$method.': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine().'. Recreating connection and retrying to call method...');
                unset($this->sock);
                $this->sock = new Connection($this->settings['connection']['ip_address'], $this->settings['connection']['port'], $this->settings['connection']['protocol']);
                continue;
            }
            if ($server_answer == null) {
                throw new Exception('An error occurred while calling method '.$method.'.');
            }
            $deserialized = $this->tl->deserialize(Tools::fopen_and_write('php://memory', 'rw+b', $server_answer));

            return $this->handle_response($deserialized, $method, $args);
        }
        throw new Exception('An error occurred while calling method '.$method.'.');
    }

    public function object_call($object, $kwargs)
    {
        foreach (range(1, $this->settings['max_tries']['query']) as $i) {
            try {
                $this->send_message($this->tl->serialize_obj($object, $kwargs), $this->tl->content_related($object));
//                $server_answer = $this->recv_message();
            } catch (Exception $e) {
                $this->log->log('An error occurred while calling object '.$object.': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine().'. Recreating connection and retrying to call object...');
                unset($this->sock);
                $this->sock = new Connection($this->settings['connection']['ip_address'], $this->settings['connection']['port'], $this->settings['connection']['protocol']);
                continue;
            }

            return;
//            if ($server_answer == null) {
//                throw new Exception('An error occurred while calling object '.$object.'.');
//            }
//            $deserialized = $this->tl->deserialize(Tools::fopen_and_write('php://memory', 'rw+b', $server_answer));
//            return $deserialized;
        }
        throw new Exception('An error occurred while calling object '.$object.'.');
    }

    public function handle_response($response, $name, $args)
    {
        switch ($response['_']) {
            case 'rpc_result':
                $this->ack_incoming_message_id($this->last_received['message_id']); // Acknowledge that I received the server's response
                $this->ack_outgoing_message_id($response['req_msg_id']); // Acknowledge that the server received my
                if ($response['req_msg_id'] != $this->last_sent['message_id']) {
                    throw new Exception('Message id mismatch; req_msg_id ('.$response['req_msg_id'].') != last sent msg id ('.$this->last_sent['message_id'].').');
                }

                return $this->handle_response($response['result'], $name, $args);
                break;
            case 'rpc_error':
                throw new Exception('Got rpc error '.$response['error_code'].': '.$response['error_message']);
                break;
            case 'rpc_answer_unknown':
                $this->ack_outgoing_message_id($this->last_sent['message_id']); // Acknowledge that the server received my message
                return $response; // I'm not handling this error
                break;
            case 'rpc_answer_dropped_running':
                $this->ack_incoming_message_id($this->last_received['message_id']); // Acknowledge that I received the server's response
                $this->ack_outgoing_message_id($this->last_sent['message_id']); // Acknowledge that the server received my message

                $this->ack_outgoing_message_id($response['req_msg_id']); // Acknowledge that the server received the original query (the same one, the response to which we wish to forget)
                return $response; // I'm not handling this
                break;
            case 'rpc_answer_dropped':
                $this->ack_incoming_message_id($this->last_received['message_id']); // Acknowledge that I received the server's response
                $this->ack_outgoing_message_id($this->last_sent['message_id']); // Acknowledge that the server received my message

                $this->ack_outgoing_message_id($response['req_msg_id']); // Acknowledge that the server received the original query (the same one, the response to which we wish to forget)
                return $response; // I'm not handling this
                break;
            case 'future_salts':
                $this->ack_outgoing_message_id($this->last_sent['message_id']); // Acknowledge that the server received my message
                if ($response['req_msg_id'] != $this->last_sent['message_id']) {
                    throw new Exception('Message id mismatch; req_msg_id ('.$response['req_msg_id'].') != last sent msg id ('.$this->last_sent['message_id'].').');
                }
        $this->log->log('Received future salts.');
                $this->future_salts = $response['salts'];
                break;
            case 'pong':
                $this->ack_incoming_message_id($this->last_received['message_id']); // Acknowledge that I received the server's response
                $this->ack_outgoing_message_id($this->last_sent['message_id']); // Acknowledge that the server received my message
                $this->log->log('pong');
                break;
            case 'msgs_ack':
                foreach ($response['msg_ids'] as $msg_id) {
                    $this->ack_outgoing_message_id($msg_id); // Acknowledge that the server received my message
                }
                break;
            case 'new_session_created':
                $this->ack_incoming_message_id($this->last_received['message_id']); // Acknowledge that I received the server's response
                $this->log->log('new session created');
                $this->log->log($response);
                break;
            case 'msg_container':
                $responses = [];
                $this->log->log('Received container.');
                $this->log->log($response['messages']);
                foreach ($response['messages'] as $message) {
                    $this->last_recieved = ['message_id' => $message['msg_id'], 'seq_no' => $message['seqno']];
                    $responses[] = $this->handle_response($message['body'], $name, $args);
                }
                foreach ($responses as $response) {
                    if ($response != null) {
                        return $response;
                    }
                }
                break;
            case 'msg_copy':
                $this->handle_response($response['orig_message'], $name, $args);
                break;
            case 'gzip_packed':
                return $this->handle_response(gzdecode($response));
                break;
            case 'http_wait':
                $this->log->log('Received http wait.');
                $this->log->log($response);
                break;
            default:
                $this->ack_incoming_message_id($this->last_received['message_id']); // Acknowledge that I received the server's response
                return $response;
                break;
        }
    }

    public function create_auth_key($expires_in = -1)
    {
        foreach (Tools::range(0, $this->settings['max_tries']['authorization']) as $retry_id_total) {
            // Make pq request
            $nonce = \phpseclib\Crypt\Random::string(16);
            $this->log->log('Handshake: Requesting pq');
            $ResPQ = $this->method_call('req_pq', ['nonce' => $nonce]);
            $server_nonce = $ResPQ['server_nonce'];
            if ($ResPQ['nonce'] !== $nonce) {
                throw new Exception('Handshake: wrong nonce');
            }
            foreach ($ResPQ['server_public_key_fingerprints'] as $curfp) {
                $curfp_biginteger = new \phpseclib\Math\BigInteger($curfp);
                if ($this->key->fp->equals($curfp_biginteger)) {
                    $public_key_fingerprint = $curfp;
                    break;
                }
            }
            if (!isset($public_key_fingerprint)) {
                throw new Exception("Handshake: couldn't find our key in the server_public_key_fingerprints vector.");
            }
            $pq_bytes = $ResPQ['pq'];
            // Compute p and q
            $pq = new \phpseclib\Math\BigInteger($pq_bytes, 256);
            list($p, $q) = $this->PrimeModule->primefactors($pq);
            $p = new \phpseclib\Math\BigInteger($p);
            $q = new \phpseclib\Math\BigInteger($q);
            if ($p->compare($q) > 0) {
                list($p, $q) = [$q, $p];
            }
            if (!($pq->equals($p->multiply($q)) && $p->compare($q) < 0)) {
                throw new Exception("Handshake: couldn't compute p and q.");
            }


            $this->log->log(sprintf('Factorization %s = %s * %s', $pq, $p, $q));

            // Serialize object for req_DH_params
            $p_bytes = $this->struct->pack('>I', (string) $p);
            $q_bytes = $this->struct->pack('>I', (string) $q);

            $new_nonce = \phpseclib\Crypt\Random::string(32);
            if ($expires_in < 0) {
                $data = $this->tl->serialize_obj('p_q_inner_data', ['pq' => $pq_bytes, 'p' => $p_bytes, 'q' => $q_bytes, 'nonce' => $nonce, 'server_nonce' => $server_nonce, 'new_nonce' => $new_nonce]);
            } else {
                $data = $this->tl->serialize_obj('p_q_inner_data_temp', ['pq' => $pq_bytes, 'p' => $p_bytes, 'q' => $q_bytes, 'nonce' => $nonce, 'server_nonce' => $server_nonce, 'new_nonce' => $new_nonce, 'expires_in' => $expires_in]);
            }
            $sha_digest = sha1($data, true);

            // Encrypt serialized object
            $random_bytes = \phpseclib\Crypt\Random::string(255 - strlen($data) - strlen($sha_digest));
            $to_encrypt = $sha_digest.$data.$random_bytes;
            $encrypted_data = $this->key->encrypt($to_encrypt);

            // req_DH_params
            $this->log->log('Starting Diffie Hellman key exchange');
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
            // Check nonce and server_nonce
            if ($nonce != $server_dh_params['nonce']) {
                throw new Exception('Handshake: wrong nonce.');
            }
            if ($server_nonce != $server_dh_params['server_nonce']) {
                throw new Exception('Handshake: wrong server nonce.');
            }
            if (isset($server_dh_params['new_nonce_hash']) && substr(sha1($new_nonce), -32) != $server_dh_params['new_nonce_hash']) {
                throw new Exception('Handshake: wrong new nonce hash.');
            }

            // Get key and iv and decrypt answer
            $encrypted_answer = $server_dh_params['encrypted_answer'];
            $tmp_aes_key = sha1($new_nonce.$server_nonce, true).substr(sha1($server_nonce.$new_nonce, true), 0, 12);
            $tmp_aes_iv = substr(sha1($server_nonce.$new_nonce, true), 12, 8).sha1($new_nonce.$new_nonce, true).substr($new_nonce, 0, 4);
            $answer_with_hash = Crypt::ige_decrypt($encrypted_answer, $tmp_aes_key, $tmp_aes_iv);

            // Separate answer and hash
            $answer_hash = substr($answer_with_hash, 0, 20);
            $answer = substr($answer_with_hash, 20);

            // Deserialize
            $server_DH_inner_data = $this->tl->deserialize(Tools::fopen_and_write('php://memory', 'rw+b', $answer));

            // Do some checks
            $server_DH_inner_data_length = $this->tl->get_length(Tools::fopen_and_write('php://memory', 'rw+b', $answer));
            if (sha1(substr($answer, 0, $server_DH_inner_data_length), true) != $answer_hash) {
                throw new Exception('Handshake: answer_hash mismatch.');
            }
            if ($nonce != $server_DH_inner_data['nonce']) {
                throw new Exception('Handshake: wrong nonce');
            }
            if ($server_nonce != $server_DH_inner_data['server_nonce']) {
                throw new Exception('Handshake: wrong server nonce');
            }
            $g = new \phpseclib\Math\BigInteger($server_DH_inner_data['g']);
            $g_a = new \phpseclib\Math\BigInteger($server_DH_inner_data['g_a'], 256);
            $dh_prime = new \phpseclib\Math\BigInteger($server_DH_inner_data['dh_prime'], 256);

            // Time delta
            $server_time = $server_DH_inner_data['server_time'];
            $this->timedelta = ($server_time - time());
            $this->log->log(sprintf('Server-client time delta = %.1f s', $this->timedelta));


            // Define some needed numbers for BigInteger
            $one = new \phpseclib\Math\BigInteger(1);
            $two = new \phpseclib\Math\BigInteger(2);
            $twoe2047 = new \phpseclib\Math\BigInteger('16158503035655503650357438344334975980222051334857742016065172713762327569433945446598600705761456731844358980460949009747059779575245460547544076193224141560315438683650498045875098875194826053398028819192033784138396109321309878080919047169238085235290822926018152521443787945770532904303776199561965192760957166694834171210342487393282284747428088017663161029038902829665513096354230157075129296432088558362971801859230928678799175576150822952201848806616643615613562842355410104862578550863465661734839271290328348967522998634176499319107762583194718667771801067716614802322659239302476074096777926805529798115328');
            $twoe2048 = new \phpseclib\Math\BigInteger('32317006071311007300714876688669951960444102669715484032130345427524655138867890893197201411522913463688717960921898019494119559150490921095088152386448283120630877367300996091750197750389652106796057638384067568276792218642619756161838094338476170470581645852036305042887575891541065808607552399123930385521914333389668342420684974786564569494856176035326322058077805659331026192708460314150258592864177116725943603718461857357598351152301645904403697613233287231227125684710820209725157101726931323469678542580656697935045997268352998638215525166389437335543602135433229604645318478604952148193555853611059596230656');

            // Check validity of dh_prime
            if (!$dh_prime->isPrime()) {
                throw new Exception("Handshake: dh_prime isn't a safe 2048-bit prime (dh_prime isn't a prime).");
            }
            /*
            // Almost always fails
            if (!$dh_prime->subtract($one)->divide($two)[0]->isPrime()) {
                throw new Exception("Handshake: dh_prime isn't a safe 2048-bit prime ((dh_prime - 1) / 2 isn't a prime).");
            }
            */
            // 2^2047 < dh_prime < 2^2048
            if ($dh_prime->compare($twoe2047) <= 0 // 2^2047 < dh_prime or dh_prime > 2^2047 or ! dh_prime <= 2^2047
            || $dh_prime->compare($twoe2048) >= 0 // dh_prime < 2^2048 or ! dh_prime >= 2^2048
            ) {
                throw new Exception("Handshake: g isn't a safe 2048-bit prime (2^2047 < dh_prime < 2^2048 is false).");
            }

            // Check validity of g
            // 1 < g < dh_prime - 1
            if ($g->compare($one) <= 0 // 1 < g or g > 1 or ! g <= 1
            || $g->compare($dh_prime->subtract($one)) >= 0 // g < dh_prime - 1 or ! g >= dh_prime - 1
            ) {
                throw new Exception('Handshake: g is invalid (1 < g < dh_prime - 1 is false).');
            }

            // Check validity of g_a
            // 1 < g_a < dh_prime - 1
            if ($g_a->compare($one) <= 0 // 1 < g_a or g_a > 1 or ! g_a <= 1
            || $g_a->compare($dh_prime->subtract($one)) >= 0 // g_a < dh_prime - 1 or ! g_a >= dh_prime - 1
            ) {
                throw new Exception('Handshake: g_a is invalid (1 < g_a < dh_prime - 1 is false).');
            }

            foreach (Tools::range(0, $this->settings['max_tries']['authorization']) as $retry_id) {
                $b = new \phpseclib\Math\BigInteger(\phpseclib\Crypt\Random::string(256), 256);
                $g_b = $g->powMod($b, $dh_prime);

                // Check validity of g_b
                // 1 < g_b < dh_prime - 1
                if ($g_b->compare($one) <= 0 // 1 < g_b or g_b > 1 or ! g_b <= 1
                || $g_b->compare($dh_prime->subtract($one)) >= 0 // g_b < dh_prime - 1 or ! g_b >= dh_prime - 1
                ) {
                    throw new Exception('Handshake: g_b is invalid (1 < g_b < dh_prime - 1 is false).');
                }

                $g_b_str = $g_b->toBytes();

                // serialize client_DH_inner_data
                $data = $this->tl->serialize_obj('client_DH_inner_data', ['nonce' => $nonce, 'server_nonce' => $server_nonce, 'retry_id' => $retry_id, 'g_b' => $g_b_str]);
                $data_with_sha = sha1($data, true).$data;
                $data_with_sha_padded = $data_with_sha.\phpseclib\Crypt\Random::string(Tools::posmod(-strlen($data_with_sha), 16));

                // encrypt client_DH_inner_data
                $encrypted_data = Crypt::ige_encrypt($data_with_sha_padded, $tmp_aes_key, $tmp_aes_iv);

                // Send set_client_DH_params query
                $Set_client_DH_params_answer = $this->method_call('set_client_DH_params', ['nonce' => $nonce, 'server_nonce' => $server_nonce, 'encrypted_data' => $encrypted_data]);

                // Generate auth_key
                $auth_key = $g_a->powMod($b, $dh_prime);
                $auth_key_str = $auth_key->toBytes();
                $auth_key_sha = sha1($auth_key_str, true);
                $auth_key_aux_hash = substr($auth_key_sha, 0, 8);
                $new_nonce_hash1 = substr(sha1($new_nonce.chr(1).$auth_key_aux_hash, true), -16);
                $new_nonce_hash2 = substr(sha1($new_nonce.chr(2).$auth_key_aux_hash, true), -16);
                $new_nonce_hash3 = substr(sha1($new_nonce.chr(3).$auth_key_aux_hash, true), -16);

                if ($Set_client_DH_params_answer['nonce'] != $nonce) {
                    throw new Exception('Handshake: wrong nonce.');
                }
                if ($Set_client_DH_params_answer['server_nonce'] != $server_nonce) {
                    throw new Exception('Handshake: wrong server nonce');
                }
                if ($Set_client_DH_params_answer['_'] == 'dh_gen_ok') {
                    if ($Set_client_DH_params_answer['new_nonce_hash1'] != $new_nonce_hash1) {
                        throw new Exception('Handshake: wrong new_nonce_hash1');
                    }
                    $this->log->log('Diffie Hellman key exchange processed successfully');

                    $res_authorization = ['server_salt' => substr($new_nonce, 0, 8 - 0) ^ substr($server_nonce, 0, 8 - 0)];
                    $res_authorization['auth_key'] = $auth_key_str;
                    $res_authorization['id'] = substr($auth_key_sha, -8);
                    if ($expires_in < 0) {
                        $res_authorization['expires_in'] = $expires_in;
                    }
                    $this->log->log('Auth key generated');
                    $this->timedelta = 0;

                    return $res_authorization;
                } elseif ($Set_client_DH_params_answer['_'] == 'dh_gen_retry') {
                    if ($Set_client_DH_params_answer['new_nonce_hash2'] != $new_nonce_hash2) {
                        throw new Exception('Handshake: wrong new_nonce_hash_2');
                    }
                    $this->log->log('Retrying Auth');
                } elseif ($Set_client_DH_params_answer['_'] == 'dh_gen_fail') {
                    if ($Set_client_DH_params_answer['new_nonce_hash3'] != $new_nonce_hash3) {
                        throw new Exception('Handshake: wrong new_nonce_hash_3');
                    }
                    $this->log->log('Auth Failed');
                    break;
                } else {
                    throw new Exception('Response Error');
                }
            }
        }
        throw new Exception('Auth Failed');
    }

    public function aes_calculate($msg_key, $direction = 'to server')
    {
        $x = ($direction == 'to server') ? 0 : 8;
        $sha1_a = sha1($msg_key.substr($this->settings['authorization']['temp_auth_key']['auth_key'], $x, ($x + 32) - $x), true);
        $sha1_b = sha1(substr($this->settings['authorization']['temp_auth_key']['auth_key'], ($x + 32), ($x + 48) - ($x + 32)).$msg_key.substr($this->settings['authorization']['temp_auth_key']['auth_key'], (48 + $x), (64 + $x) - (48 + $x)), true);
        $sha1_c = sha1(substr($this->settings['authorization']['temp_auth_key']['auth_key'], ($x + 64), ($x + 96) - ($x + 64)).$msg_key, true);
        $sha1_d = sha1($msg_key.substr($this->settings['authorization']['temp_auth_key']['auth_key'], ($x + 96), ($x + 128) - ($x + 96)), true);
        $aes_key = substr($sha1_a, 0, 8 - 0).substr($sha1_b, 8, 20 - 8).substr($sha1_c, 4, 16 - 4);
        $aes_iv = substr($sha1_a, 8, 20 - 8).substr($sha1_b, 0, 8 - 0).substr($sha1_c, 16, 20 - 16).substr($sha1_d, 0, 8 - 0);

        return [$aes_key, $aes_iv];
    }
}
