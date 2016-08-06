<?php

set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).DIRECTORY_SEPARATOR.'libpy2php');
require_once 'libpy2php.php';
require_once 'os_path.php';
require_once 'crypt.php';
require_once 'prime.php';
require_once 'TL.php';
require_once 'vendor/autoload.php';
$struct = new \danog\PHP\StructClass();
/**
 * Function to get hex crc32
 * :param data: Data to encode.
 */
function newcrc32($data)
{
    return hexdec(hash('crc32b', $data));
}

/**
 * Function to dump the hex version of a string.
 *
 * @param $what What to dump.
 */
function hex_dump(...$what)
{
    foreach ($what as $w) {
        var_dump(bin2hex($w));
    }
}
/**
 * len.
 *
 * Get the length of a string or of an array
 *
 * @param   $input String or array to parse
 *
 * @return int with the length
 **/
function len($input)
{
    if (is_array($input)) {
        return count($input);
    }

    return strlen($input);
}

/**
 * Function to visualize byte streams. Split into bytes, print to console.
 * :param bs: BYTE STRING.
 */
function vis($bs)
{
    $bs = str_split($bs);
    $symbols_in_one_line = 8;
    $n = floor(len($bs) / $symbols_in_one_line);
    $i = 0;
    foreach (pyjslib_range($n) as $i) {
        echo $i * $symbols_in_one_line.' | '.implode(' ',
            array_map(function ($el) {
                return bin2hex($el);
            }, array_slice($bs, $i * $symbols_in_one_line, ($i + 1) * $symbols_in_one_line))
        ).PHP_EOL;
    }
    if (len($bs) % $symbols_in_one_line != 0) {
        echo($i + 1) * $symbols_in_one_line.' | '.implode(' ',
            array_map(function ($el) {
                return bin2hex($el);
            }, array_slice($bs, ($i + 1) * $symbols_in_one_line))
        ).PHP_EOL;
    }
}
/**
 * posmod(numeric,numeric) : numeric
 * Works just like the % (modulus) operator, only returns always a postive number.
 */
function posmod($a, $b)
{
    $resto = $a % $b;
    if ($resto < 0) {
        $resto += abs($b);
    }

    return $resto;
}

function fread_all($handle)
{
    $pos = ftell($handle);
    fseek($handle, 0);
    $content = fread($handle, fstat($handle)['size']);
    fseek($handle, $pos);

    return $content;
}
function fopen_and_write($filename, $mode, $data)
{
    $handle = fopen($filename, $mode);
    fwrite($handle, $data);
    rewind($handle);

    return $handle;
}
function string2bin($string)
{
    $res = null;
    foreach (explode('\\', $string) as $s) {
        if ($s != null && strlen($s) == 3) {
            $res .= hex2bin(substr($s, 1));
        }
    }

    return $res;
}
/**
 * Manages TCP Transport. encryption and message frames.
 */
class Session
{
    public function __construct($ip, $port, $auth_key = null, $server_salt = null)
    {
        $this->sock = fsockopen('tcp://'.$ip.':'.$port);
        if (!(get_resource_type($this->sock) == 'file' || get_resource_type($this->sock) == 'stream')) {
            throw new Exception("Couldn't connect to socket.");
        }
        $this->number = 0;
        $this->timedelta = 0;
        $this->session_id = \phpseclib\Crypt\Random::string(8);
        $this->auth_key = $auth_key;
        $this->auth_key_id = $this->auth_key ? substr(sha1($this->auth_key, true), -8) : null;
        stream_set_timeout($this->sock, 5);
        $this->MAX_RETRY = 5;
        $this->AUTH_MAX_RETRY = 5;
        $this->struct = new \danog\PHP\Struct();
        $this->PrimeModule = new PrimeModule();
        try {
            $this->tl = new TL('https://core.telegram.org/schema/mtproto-json');
        } catch (Exception $e) {
            $this->tl = new TL(__DIR__.'/TL_schema.JSON');
        }
    }

    public function __destruct()
    {
        fclose($this->sock);
    }

    /**
     * Forming the message frame and sending message to server
     * :param message: byte string to send.
     */
    public function send_message($message_data)
    {
        $message_id = $this->struct->pack('<Q', (int) ((time() + $this->timedelta) * pow(2, 30)) * 4);

        if (($this->auth_key == null) || ($this->server_salt == null)) {
            $message = string2bin('\x00\x00\x00\x00\x00\x00\x00\x00').$message_id.$this->struct->pack('<I', strlen($message_data)).$message_data;
        } else {
            $encrypted_data =
                $this->server_salt.$this->session_id.$message_id.$this->struct->pack('<II', $this->number, strlen($message_data)).$message_data;
            $message_key = substr(sha1($encrypted_data, true), -16);
            $padding = \phpseclib\Crypt\Random::string(posmod(-strlen($encrypted_data), 16));
            echo strlen($encrypted_data.$padding).PHP_EOL;
            list($aes_key, $aes_iv) = $this->aes_calculate($message_key);
            $message = $this->auth_key_id.$message_key.crypt::ige_encrypt($encrypted_data.$padding, $aes_key, $aes_iv);
        }
        $step1 = $this->struct->pack('<II', (strlen($message) + 12), $this->number).$message;
        $step2 = $step1.$this->struct->pack('<I', newcrc32($step1));
        fwrite($this->sock, $step2);
        $this->number += 1;
    }

    /**
     * Reading socket and receiving message from server. Check the CRC32.
     */
    public function recv_message()
    {
        $packet_length_data = fread($this->sock, 4);
        if (len($packet_length_data) < 4) {
            throw new Exception('Nothing in the socket!');
        }
        $packet_length = $this->struct->unpack('<I', $packet_length_data)[0];
        $packet = fread($this->sock, ($packet_length - 4));
        if (!(newcrc32($packet_length_data.substr($packet, 0, -4)) == $this->struct->unpack('<I', substr($packet, -4))[0])) {
            throw new Exception('CRC32 was not correct!');
        }
        $x = $this->struct->unpack('<I', substr($packet, 0, 4));
        $auth_key_id = substr($packet, 4, 8);
        hex_dump($packet);
        if ($auth_key_id == string2bin('\x00\x00\x00\x00\x00\x00\x00\x00')) {
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
                echo 'An error occurred while calling method '.$method.': '.$e->getMessage().PHP_EOL.'Stack trace:'.$e->getTraceAsString().PHP_EOL.'Retrying to call method...'.PHP_EOL.PHP_EOL;
                continue;
            }
            if ($server_answer == null) {
                throw new Exception('An error occurred while calling method '.$method.'.');
            }

            return $this->tl->deserialize(fopen_and_write('php://memory', 'rw+b', $server_answer));
        }
        throw new Exception('An error occurred while calling method '.$method.'.');
    }

    public function create_auth_key()
    {
        $f = file_get_contents(__DIR__.'/rsa.pub');
        $key = new \phpseclib\Crypt\RSA();
        $key->load($f);
        $nonce = \phpseclib\Crypt\Random::string(16);
        pyjslib_printnl('Requesting pq');
        $ResPQ = $this->method_call('req_pq', ['nonce' => $nonce]);
        if ($ResPQ['nonce'] !== $nonce) {
            throw new Exception('Handshake: wrong nonce');
        }
        $server_nonce = $ResPQ['server_nonce'];
        $public_key_fingerprint = (int) $ResPQ['server_public_key_fingerprints'][0];
        $pq_bytes = $ResPQ['pq'];
var_dump(new \phpseclib\Math\BigInteger($public_key_fingerprint), $key->getPublicKeyFingerprint('sha1'));

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
        pyjslib_printnl(sprintf('Factorization %s = %s * %s', $pq, $p, $q));
        $p_bytes = $this->struct->pack('>Q', (string) $p);
        $q_bytes = $this->struct->pack('>Q', (string) $q);
        $new_nonce = \phpseclib\Crypt\Random::string(32);
        $data = $this->tl->serialize_obj('p_q_inner_data', ['pq' => $pq_bytes, 'p' => $p_bytes, 'q' => $q_bytes, 'nonce' => $nonce, 'server_nonce' => $server_nonce, 'new_nonce' => $new_nonce]);
        $sha_digest = sha1($data, true);
        $random_bytes = \phpseclib\Crypt\Random::string(255 - strlen($data) - strlen($sha_digest));
        $to_encrypt = $sha_digest.$data.$random_bytes;
        $encrypted_data = $key->_raw_encrypt($to_encrypt);
        pyjslib_printnl('Starting Diffie Hellman key exchange');
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
        $server_DH_inner_data = deserialize(fopen_and_write('php://memory', 'rw+b', $answer));
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
        pyjslib_printnl(sprintf('Server-client time delta = %.1f s', $this->timedelta));
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
                pyjslib_printnl('Diffie Hellman key exchange processed successfully');
                $this->server_salt = new strxor(substr($new_nonce, 0, 8 - 0), substr($server_nonce, 0, 8 - 0));
                $this->auth_key = $auth_key_str;
                $this->auth_key_id = substr($auth_key_sha, -8);
                pyjslib_printnl('Auth key generated');

                return 'Auth Ok';
            } elseif ($Set_client_DH_params_answer->name == 'dh_gen_retry') {
                if ($Set_client_DH_params_answer['new_nonce_hash2'] != $new_nonce_hash2) {
                    throw new Exception('Handshake: wrong new_nonce_hash_2');
                }
                pyjslib_printnl('Retry Auth');
            } elseif ($Set_client_DH_params_answer->name == 'dh_gen_fail') {
                if ($Set_client_DH_params_answer['new_nonce_hash3'] != $new_nonce_hash3) {
                    throw new Exception('Handshake: wrong new_nonce_hash_3');
                }
                pyjslib_printnl('Auth Failed');
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
