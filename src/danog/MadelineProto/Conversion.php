<?php

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto;

class Conversion
{
    public static function random($length)
    {
        return $length === 0 ? '' : \phpseclib\Crypt\Random::string($length);
    }

    public static function unpack_signed_int($value)
    {
        if (strlen($value) !== 4) {
            throw new TL\Exception(\danog\MadelineProto\Lang::$current_lang['length_not_4']);
        }

        return unpack('l', !\danog\MadelineProto\Magic::$BIG_ENDIAN ? strrev($value) : $value)[1];
    }

    public static function pack_signed_int($value)
    {
        if ($value > 2147483647) {
            throw new TL\Exception(sprintf(\danog\MadelineProto\Lang::$current_lang['value_bigger_than_2147483647'], $value));
        }
        if ($value < -2147483648) {
            throw new TL\Exception(sprintf(\danog\MadelineProto\Lang::$current_lang['value_smaller_than_2147483648'], $value));
        }
        $res = pack('l', $value);

        return !\danog\MadelineProto\Magic::$BIG_ENDIAN ? strrev($res) : $res;
    }


    public static function old_aes_calculate($msg_key, $auth_key, $to_server = true)
    {
        $x = $to_server ? 0 : 8;
        $sha1_a = sha1($msg_key.substr($auth_key, $x, 32), true);
        $sha1_b = sha1(substr($auth_key, 32 + $x, 16).$msg_key.substr($auth_key, 48 + $x, 16), true);
        $sha1_c = sha1(substr($auth_key, 64 + $x, 32).$msg_key, true);
        $sha1_d = sha1($msg_key.substr($auth_key, 96 + $x, 32), true);
        $aes_key = substr($sha1_a, 0, 8).substr($sha1_b, 8, 12).substr($sha1_c, 4, 12);
        $aes_iv = substr($sha1_a, 8, 12).substr($sha1_b, 0, 8).substr($sha1_c, 16, 4).substr($sha1_d, 0, 8);

        return [$aes_key, $aes_iv];
    }

    public static function ige_decrypt($message, $key, $iv)
    {
        $cipher = new \phpseclib\Crypt\AES('ige');
        $cipher->setKey($key);
        $cipher->setIV($iv);

        return @$cipher->decrypt($message);
    }

    public static function telethon($session, $new_session, $settings = [])
    {
        set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
        if (!extension_loaded('sqlite3')) {
            throw new Exception(['extension', 'sqlite3']);
        }
        if (!isset(pathinfo($session)['extension'])) {
            $session .= '.session';
        }
        $session = Absolute::absolute($session);
        $sqlite = new \PDO("sqlite:$session");
        $sqlite->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);

        $sessions = $sqlite->query("SELECT * FROM sessions")->fetchAll();
        $MadelineProto = new \danog\MadelineProto\API($new_session, $settings);
        foreach ($sessions as $dc) {
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->auth_key = ['server_salt' => '', 'connection_inited' => true, 'id' => substr(sha1($dc['auth_key'], true), -8), 'auth_key' => $dc['auth_key']];
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->temp_auth_key = NULL;
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->ip = $dc['server_address'];
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->port = $dc['port'];
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->authorized = true;
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->session_id = $MadelineProto->random(8);
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->session_in_seq_no = 0;
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->session_out_seq_no = 0;
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->incoming_messages = [];
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->outgoing_messages = [];
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->new_outgoing = [];
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->incoming = [];
        }
        $MadelineProto->API->authorized = MTProto::LOGGED_IN;
        $MadelineProto->API->init_authorization();
        return $MadelineProto;
    }

    public static function pyrogram($session, $new_session, $settings = [])
    {
        set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
        if (!isset(pathinfo($session)['extension'])) {
            $session .= '.session';
        }
        $session = Absolute::absolute($session);
        $session = json_decode(file_get_contents($session), true);
        $session['auth_key'] = base64_decode(implode('', $session['auth_key']));

        $settings['connection_settings']['all']['test_mode'] = $session['test_mode'];

        $MadelineProto = new \danog\MadelineProto\API($new_session, $settings);

        $MadelineProto->API->datacenter->sockets[$session['dc_id']]->auth_key = ['server_salt' => '', 'connection_inited' => true, 'id' => substr(sha1($session['auth_key'], true), -8), 'auth_key' => $session['auth_key']];
        $MadelineProto->API->datacenter->sockets[$session['dc_id']]->temp_auth_key = NULL;
        $MadelineProto->API->datacenter->sockets[$session['dc_id']]->authorized = true;
        $MadelineProto->API->datacenter->sockets[$session['dc_id']]->session_id = $MadelineProto->random(8);
        $MadelineProto->API->datacenter->sockets[$session['dc_id']]->session_in_seq_no = 0;
        $MadelineProto->API->datacenter->sockets[$session['dc_id']]->session_out_seq_no = 0;
        $MadelineProto->API->datacenter->sockets[$session['dc_id']]->incoming_messages = [];
        $MadelineProto->API->datacenter->sockets[$session['dc_id']]->outgoing_messages = [];
        $MadelineProto->API->datacenter->sockets[$session['dc_id']]->new_outgoing = [];
        $MadelineProto->API->datacenter->sockets[$session['dc_id']]->incoming = [];

        $MadelineProto->API->authorized = MTProto::LOGGED_IN;
        $MadelineProto->API->init_authorization();
        return $MadelineProto;
    }

    public static function tdesktop_md5($data) {
        $result = '';
        foreach (str_split(md5($data), 2) as $byte) {
            $result .= strrev($byte);
        }
        return strtoupper($result);
    }

    const FILEOPTION_SAFE = 1;
    const FILEOPTION_USER = 2;
    public static $tdesktop_base_path;
    public static $tdesktop_user_base_path;
    public static $tdesktop_key;
    public static function tdesktop_fopen($fileName, $options = 3) {
        $name = ($options & self::FILEOPTION_USER ? self::$tdesktop_user_base_path : self::$tdesktop_base_path) . $fileName;
        $totry = [];
        for ($x = 0; $x <= 1; $x++) {
            if (file_exists($name.$x)) $totry []= fopen($name.$x, 'rb');
        }
        foreach ($totry as $fp) {
            if (stream_get_contents($fp, 4) !== 'TDF$') {
                \danog\MadelineProto\Logger::log('Wrong magic', Logger::ERROR);
                continue;
            }
            $versionBytes = stream_get_contents($fp, 4);
            $version = self::unpack_signed_int($versionBytes);
            \danog\MadelineProto\Logger::log("TDesktop version: $version");
            $data = stream_get_contents($fp);
            $md5 = substr($data, -16);
            $data = substr($data, 0, -16);

            $length = pack('l', strlen($data));
            $length = \danog\MadelineProto\Magic::$BIG_ENDIAN ? strrev($length) : $length;

            if (md5($data.$length.$versionBytes.'TDF$', true) !== $md5) {
                \danog\MadelineProto\Logger::log('Wrong MD5', Logger::ERROR);
            }
            $res = fopen('php://memory', 'rw+b');
            fwrite($res, $data);
            fseek($res, 0);
            return $res;
        }
        throw new Exception("Could not open $fileName");
    }
    public static function tdesktop_fopen_encrypted($fileName, $options = 3) {
        $f = self::tdesktop_fopen($fileName, $options);
        $data = self::tdesktop_read_bytearray($f);
        return self::tdesktop_decrypt($data, self::$tdesktop_key);
    }
    public static function tdesktop_read_bytearray($fp) {
        $length = self::unpack_signed_int(stream_get_contents($fp, 4));
        $data = $length ? stream_get_contents($fp, $length) : '';
        $res = fopen('php://memory', 'rw+b');
        fwrite($res, $data);
        fseek($res, 0);
        return $res;
    }
    public static function tdesktop_decrypt($data, $auth_key) {
        $message_key = stream_get_contents($data, 16);
        $encrypted_data = stream_get_contents($data);

        list($aes_key, $aes_iv) = self::old_aes_calculate($message_key, $auth_key, false);
        $decrypted_data = self::ige_decrypt($encrypted_data, $aes_key, $aes_iv);

        if ($message_key != substr(sha1($decrypted_data, true), 0, 16)) {
             throw new \danog\MadelineProto\SecurityException('msg_key mismatch');
        }

        $res = fopen('php://memory', 'rw+b');
        fwrite($res, $decrypted_data);
        fseek($res, 0);
        return $res;
    }
    public static function tdesktop($session, $new_session, $settings = []) {
        set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
        if (!isset($settings['old_session_key'])) $settings['old_session_key'] = 'data';
        if (!isset($settings['old_session_passcode'])) $settings['old_session_passcode'] = '';
        list($part_one_md5, $part_two_md5) = str_split(self::tdesktop_md5($settings['old_session_key']), 16);

        self::$tdesktop_base_path = $session.'/';
        self::$tdesktop_user_base_path = self::$tdesktop_base_path.$part_one_md5.'/';

        $data = self::tdesktop_fopen('map');

        $salt = self::tdesktop_read_bytearray($data);
        $salt = fstat($salt)['size'] ? $salt : self::random(32);
        $encryptedKey = self::tdesktop_read_bytearray($data);

        $keyIterCount = strlen($settings['old_session_passcode']) ? 4000 : 4;

        $passKey = openssl_pbkdf2($settings['old_session_passcode'], stream_get_contents($salt), 256, $keyIterCount);
        self::$tdesktop_key = stream_get_contents(self::tdesktop_read_bytearray(self::tdesktop_decrypt($encryptedKey, $passKey)));

        $main = self::tdesktop_fopen_encrypted($part_one_md5, self::FILEOPTION_SAFE);

        $magic = self::pack_signed_int(0x4b);
        while (($crc = stream_get_contents($main, 4)) !== $magic) {
        }
        $main = self::tdesktop_read_bytearray($main);

        $user_id = self::unpack_signed_int(stream_get_contents($main, 4));
        $dc_id = self::unpack_signed_int(stream_get_contents($main, 4));
        
        $keys = [];
        $length = self::unpack_signed_int(stream_get_contents($main, 4));
        for ($x = 0; $x < $length; $x++) {
            $keys[$x]['dc_id'] = self::unpack_signed_int(stream_get_contents($main, 4));
            $keys[$x]['auth_key'] = self::tdesktop_read_bytearray($main);
        }
        var_dump($keys, $length, $dc_id, $user_id);
    }
}
