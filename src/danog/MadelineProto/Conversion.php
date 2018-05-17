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

        $sessions = $sqlite->query('SELECT * FROM sessions')->fetchAll();
        $MadelineProto = new \danog\MadelineProto\API($new_session, $settings);
        foreach ($sessions as $dc) {
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->auth_key = ['server_salt' => '', 'connection_inited' => true, 'id' => substr(sha1($dc['auth_key'], true), -8), 'auth_key' => $dc['auth_key']];
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->temp_auth_key = null;
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
        $MadelineProto->API->datacenter->sockets[$session['dc_id']]->temp_auth_key = null;
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

    public static function zerobias($session, $new_session, $settings = [])
    {
        set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
        if (is_string($session)) {
            $session = json_decode($session, true);
        }
        $dc = $session['dc'];
        $session['auth_key'] = hex2bin($session["dc$dc".'_auth_key']);

        $MadelineProto = new \danog\MadelineProto\API($new_session, $settings);

        $MadelineProto->API->datacenter->sockets[$dc]->auth_key = ['server_salt' => '', 'connection_inited' => true, 'id' => substr(sha1($session['auth_key'], true), -8), 'auth_key' => $session['auth_key']];
        $MadelineProto->API->datacenter->sockets[$dc]->temp_auth_key = null;
        $MadelineProto->API->datacenter->sockets[$dc]->authorized = true;
        $MadelineProto->API->datacenter->sockets[$dc]->session_id = $MadelineProto->random(8);
        $MadelineProto->API->datacenter->sockets[$dc]->session_in_seq_no = 0;
        $MadelineProto->API->datacenter->sockets[$dc]->session_out_seq_no = 0;
        $MadelineProto->API->datacenter->sockets[$dc]->incoming_messages = [];
        $MadelineProto->API->datacenter->sockets[$dc]->outgoing_messages = [];
        $MadelineProto->API->datacenter->sockets[$dc]->new_outgoing = [];
        $MadelineProto->API->datacenter->sockets[$dc]->incoming = [];

        $MadelineProto->API->authorized = MTProto::LOGGED_IN;
        $MadelineProto->API->init_authorization();

        return $MadelineProto;
    }

    public static function tdesktop_md5($data)
    {
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

    public static function tdesktop_fopen($fileName, $options = 3)
    {
        $name = ($options & self::FILEOPTION_USER ? self::$tdesktop_user_base_path : self::$tdesktop_base_path).$fileName;
        $totry = [];
        for ($x = 0; $x <= 1; $x++) {
            if (file_exists($name.$x)) {
                $totry[] = fopen($name.$x, 'rb');
            }
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

    public static function tdesktop_fopen_encrypted($fileName, $options = 3)
    {
        $f = self::tdesktop_fopen($fileName, $options);
        $data = self::tdesktop_read_bytearray($f);
        $res = self::tdesktop_decrypt($data, self::$tdesktop_key);
        $length = unpack('V', stream_get_contents($res, 4))[1];

        if ($length > fstat($res)['size'] || $length < 4) {
            throw new \danog\MadelineProto\Exception('Wrong length');
        }

        return $res;
    }

    public static function tdesktop_read_bytearray($fp)
    {
        $length = self::unpack_signed_int(stream_get_contents($fp, 4));
        $data = $length ? stream_get_contents($fp, $length) : '';
        $res = fopen('php://memory', 'rw+b');
        fwrite($res, $data);
        fseek($res, 0);

        return $res;
    }

    public static function tdesktop_decrypt($data, $auth_key)
    {
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

    const dbiKey = 0x00;
    const dbiUser = 0x01;
    const dbiDcOptionOldOld = 0x02;
    const dbiChatSizeMax = 0x03;
    const dbiMutePeer = 0x04;
    const dbiSendKey = 0x05;
    const dbiAutoStart = 0x06;
    const dbiStartMinimized = 0x07;
    const dbiSoundNotify = 0x08;
    const dbiWorkMode = 0x09;
    const dbiSeenTrayTooltip = 0x0a;
    const dbiDesktopNotify = 0x0b;
    const dbiAutoUpdate = 0x0c;
    const dbiLastUpdateCheck = 0x0d;
    const dbiWindowPosition = 0x0e;
    const dbiConnectionTypeOld = 0x0f;
    // 0x10 reserved
    const dbiDefaultAttach = 0x11;
    const dbiCatsAndDogs = 0x12;
    const dbiReplaceEmojis = 0x13;
    const dbiAskDownloadPath = 0x14;
    const dbiDownloadPathOld = 0x15;
    const dbiScale = 0x16;
    const dbiEmojiTabOld = 0x17;
    const dbiRecentEmojiOldOld = 0x18;
    const dbiLoggedPhoneNumber = 0x19;
    const dbiMutedPeers = 0x1a;
    // 0x1b reserved
    const dbiNotifyView = 0x1c;
    const dbiSendToMenu = 0x1d;
    const dbiCompressPastedImage = 0x1e;
    const dbiLangOld = 0x1f;
    const dbiLangFileOld = 0x20;
    const dbiTileBackground = 0x21;
    const dbiAutoLock = 0x22;
    const dbiDialogLastPath = 0x23;
    const dbiRecentEmojiOld = 0x24;
    const dbiEmojiVariantsOld = 0x25;
    const dbiRecentStickers = 0x26;
    const dbiDcOptionOld = 0x27;
    const dbiTryIPv6 = 0x28;
    const dbiSongVolume = 0x29;
    const dbiWindowsNotificationsOld = 0x30;
    const dbiIncludeMuted = 0x31;
    const dbiMegagroupSizeMax = 0x32;
    const dbiDownloadPath = 0x33;
    const dbiAutoDownload = 0x34;
    const dbiSavedGifsLimit = 0x35;
    const dbiShowingSavedGifsOld = 0x36;
    const dbiAutoPlay = 0x37;
    const dbiAdaptiveForWide = 0x38;
    const dbiHiddenPinnedMessages = 0x39;
    const dbiRecentEmoji = 0x3a;
    const dbiEmojiVariants = 0x3b;
    const dbiDialogsMode = 0x40;
    const dbiModerateMode = 0x41;
    const dbiVideoVolume = 0x42;
    const dbiStickersRecentLimit = 0x43;
    const dbiNativeNotifications = 0x44;
    const dbiNotificationsCount = 0x45;
    const dbiNotificationsCorner = 0x46;
    const dbiThemeKey = 0x47;
    const dbiDialogsWidthRatioOld = 0x48;
    const dbiUseExternalVideoPlayer = 0x49;
    const dbiDcOptions = 0x4a;
    const dbiMtpAuthorization = 0x4b;
    const dbiLastSeenWarningSeenOld = 0x4c;
    const dbiAuthSessionSettings = 0x4d;
    const dbiLangPackKey = 0x4e;
    const dbiConnectionType = 0x4f;
    const dbiStickersFavedLimit = 0x50;
    const dbiSuggestStickersByEmoji = 0x51;

    const dbiEncryptedWithSalt = 333;
    const dbiEncrypted = 444;

    // 500-600 reserved

    const dbiVersion = 666;

    public static function tdesktop($session, $new_session, $settings = [])
    {
        set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
        if (!isset($settings['old_session_key'])) {
            $settings['old_session_key'] = 'data';
        }
        if (!isset($settings['old_session_passcode'])) {
            $settings['old_session_passcode'] = '';
        }

        if (basename($session) !== 'tdata') {
            $session .= '/tdata';
        }

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
        $auth_keys = [];

        while (true) {
            $magic = self::unpack_signed_int(stream_get_contents($main, 4));
            switch ($magic) {
                case self::dbiDcOptionOldOld:
                    stream_get_contents($main, 4);
                    self::tdesktop_read_bytearray($main);
                    self::tdesktop_read_bytearray($main);
                    stream_get_contents($main, 4);
                    break;
                case self::dbiDcOptionOld:
                    stream_get_contents($main, 8);
                    self::tdesktop_read_bytearray($main);
                    stream_get_contents($main, 4);
                    break;
                case self::dbiDcOptions:
                    self::tdesktop_read_bytearray($main);
                    break;
                case self::dbiUser:
                    stream_get_contents($main, 4);
                    $main_dc_id = self::unpack_signed_int(stream_get_contents($main, 4));
                    break;
                case self::dbiKey:
                    $auth_keys[self::unpack_signed_int(stream_get_contents($main, 4))] = stream_get_contents($main, 256);
                    break;
                case self::dbiMtpAuthorization:
                    $main = self::tdesktop_read_bytearray($main);
                    //stream_get_contents($main, 4);
                    $user_id = self::unpack_signed_int(stream_get_contents($main, 4));
                    $main_dc_id = self::unpack_signed_int(stream_get_contents($main, 4));
                    $length = self::unpack_signed_int(stream_get_contents($main, 4));
                    for ($x = 0; $x < $length; $x++) {
                        $auth_keys[self::unpack_signed_int(stream_get_contents($main, 4))] = stream_get_contents($main, 256);
                    }
                    break 2;
                case self::dbiAutoDownload:
                    stream_get_contents($main, 12);
                    break;
                case self::dbiDialogsMode:
                    stream_get_contents($main, 8);
                    break;
                case self::dbiAuthSessionSettings:
                    self::tdesktop_read_bytearray($main);
                    break;
                case self::dbiConnectionTypeOld:
                    switch (self::unpack_signed_int(stream_get_contents($main, 4))) {
                        case 2:
                        case 3:
                        self::tdesktop_read_bytearray($main);
                        stream_get_contents($main, 4);
                        self::tdesktop_read_bytearray($main);
                        self::tdesktop_read_bytearray($main);
                        break;
                    }
                    break;
                case self::dbiConnectionType:
                    stream_get_contents($main, 8);
                    self::tdesktop_read_bytearray($main);
                    stream_get_contents($main, 4);
                    self::tdesktop_read_bytearray($main);
                    self::tdesktop_read_bytearray($main);
                    break;
                case self::dbiThemeKey:
                case self::dbiLangPackKey:
                case self::dbiMutePeer:
                    stream_get_contents($main, 8);
                    break;
                case self::dbiWindowPosition:
                    stream_get_contents($main, 24);
                    break;
                case self::dbiLoggedPhoneNumber:
                    self::tdesktop_read_bytearray($main);
                    break;
                case self::dbiMutedPeers:
                    $length = self::unpack_signed_int(stream_get_contents($main, 4));
                    for ($x = 0; $x < $length; $x++) {
                        stream_get_contents($main, 8);
                    }
                case self::dbiDownloadPathOld:
                    self::tdesktop_read_bytearray($main);
                    break;
                case self::dbiDialogLastPath:
                    self::tdesktop_read_bytearray($main);
                    break;
                case self::dbiDownloadPath:
                    self::tdesktop_read_bytearray($main);
                    self::tdesktop_read_bytearray($main);
                    break;
                default:
                    stream_get_contents($main, 4);
                    break;
            }
        }
        $MadelineProto = new \danog\MadelineProto\API($new_session, $settings);
        foreach ($auth_keys as $dc => $auth_key) {
            $MadelineProto->API->datacenter->sockets[$dc]->auth_key = ['server_salt' => '', 'connection_inited' => true, 'id' => substr(sha1($auth_key, true), -8), 'auth_key' => $auth_key];
            $MadelineProto->API->datacenter->sockets[$dc]->temp_auth_key = null;
            $MadelineProto->API->datacenter->sockets[$dc]->authorized = true;
            $MadelineProto->API->datacenter->sockets[$dc]->session_id = $MadelineProto->random(8);
            $MadelineProto->API->datacenter->sockets[$dc]->session_in_seq_no = 0;
            $MadelineProto->API->datacenter->sockets[$dc]->session_out_seq_no = 0;
            $MadelineProto->API->datacenter->sockets[$dc]->incoming_messages = [];
            $MadelineProto->API->datacenter->sockets[$dc]->outgoing_messages = [];
            $MadelineProto->API->datacenter->sockets[$dc]->new_outgoing = [];
            $MadelineProto->API->datacenter->sockets[$dc]->incoming = [];
        }
        $MadelineProto->API->authorized = MTProto::LOGGED_IN;
        $MadelineProto->API->authorized_dc = $main_dc_id;
        $MadelineProto->API->init_authorization();

        return $MadelineProto;
    }
}
