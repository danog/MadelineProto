<?php

declare(strict_types=1);

/**
 * Conversion module.
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

namespace danog\MadelineProto;

use danog\MadelineProto\MTProtoTools\Crypt;
use PDO;
use Webmozart\Assert\Assert;

use const DIRECTORY_SEPARATOR;

use function stream_get_contents;

final class Conversion
{
    /**
     * Prepare API instance.
     *
     * @param array<int, string> $authorization Authorization info
     */
    public static function importAuthorization(array $authorization, int $main_dc_id, string $session, ?SettingsAbstract $settings = null): API
    {
        $settingsFull = new Settings;
        if ($settings) {
            $settingsFull->merge($settings);
        }
        $settings = $settingsFull;
        $settings->getLogger()->setLevel(Logger::ULTRA_VERBOSE);
        $settings->getAuth()->setPfs(true);
        $MadelineProto = new API($session, $settings);
        $MadelineProto->help->getConfig();
        $MadelineProto->logger('About to import auth!', Logger::FATAL_ERROR);
        $MadelineProto->importAuthorization($authorization, $main_dc_id);
        return $MadelineProto;
    }
    public static function telethon(string $session, string $new_session, $settings = [])
    {
        if (!\extension_loaded('sqlite3')) {
            throw new Exception(['extension', 'sqlite3']);
        }
        if (!isset(\pathinfo($session)['extension'])) {
            $session .= '.session';
        }
        $session = Tools::absolute($session);
        $sqlite = new PDO("sqlite:$session");
        $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sessions = $sqlite->query('SELECT * FROM sessions')->fetchAll();

        $dcs = [];
        foreach ($sessions as $dc) {
            $dcs[$dc['dc_id']] = $dc['auth_key'];
        }

        return self::importAuthorization($dcs, $dc['dc_id'], $new_session, $settings);
    }

    public static function pyrogram(string $session, string $new_session, $settings = [])
    {
        \set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
        if (!isset(\pathinfo($session)['extension'])) {
            $session .= '.session';
        }
        $session = Tools::absolute($session);
        $session = \json_decode(\file_get_contents($session), true);
        $session['auth_key'] = \base64_decode(\implode('', $session['auth_key']));
        Assert::notFalse($session['auth_key']);
        Assert::integer($session['dc_id']);

        $settings['connection_settings']['all']['test_mode'] = $session['test_mode'];

        return self::importAuthorization([$session['dc_id'] => $session['auth_key']], $session['dc_id'], $new_session, $settings);
    }

    public static function zerobias($session, $new_session, $settings = [])
    {
        \set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
        if (\is_string($session)) {
            $session = \json_decode($session, true);
        }
        $dc = $session['dc'];
        $key = \hex2bin($session["dc$dc".'_auth_key']);

        Assert::integer($dc);
        Assert::string($key);
        return self::importAuthorization([$dc => $key], $dc, $new_session, $settings);
    }

    public static function tdesktop_md5($data)
    {
        $result = '';
        foreach (\str_split(\md5($data), 2) as $byte) {
            $result .= \strrev($byte);
        }

        return \strtoupper($result);
    }

    const FILEOPTION_SAFE = 1;
    const FILEOPTION_USER = 2;
    public static $tdesktop_base_path;
    public static $tdesktop_user_base_path;
    public static $tdesktop_key;

    public static function tdesktop_fopen($fileName, $options = self::FILEOPTION_SAFE|self::FILEOPTION_USER)
    {
        $name = ($options & self::FILEOPTION_USER ? self::$tdesktop_user_base_path : self::$tdesktop_base_path).$fileName;
        $totry = [];
        foreach (['0', '1', 's'] as $x) {
            if (\file_exists($name.$x)) {
                $totry[] = \fopen($name.$x, 'rb');
            }
        }
        foreach ($totry as $fp) {
            if (\stream_get_contents($fp, 4) !== 'TDF$') {
                Logger::log('Wrong magic', Logger::ERROR);
                continue;
            }
            $versionBytes = \stream_get_contents($fp, 4);
            $version = Tools::unpackSignedInt($versionBytes);
            Logger::log("TDesktop version: $version");
            $data = \stream_get_contents($fp);
            $md5 = \substr($data, -16);
            $data = \substr($data, 0, -16);

            $length = \pack('l', \strlen($data));
            $length = Magic::$BIG_ENDIAN ? \strrev($length) : $length;

            if (\md5($data.$length.$versionBytes.'TDF$', true) !== $md5) {
                Logger::log('Wrong MD5', Logger::ERROR);
            }

            $res = \fopen('php://memory', 'rw+b');
            \fwrite($res, $data);
            \fseek($res, 0);

            return $res;
        }

        throw new Exception("Could not open $fileName");
    }

    public static function tdesktop_fopen_encrypted($fileName, $options = 3)
    {
        $f = self::tdesktop_fopen($fileName, $options);
        $data = self::tdesktop_read_bytearray($f);
        $res = self::tdesktop_decrypt($data, self::$tdesktop_key);
        $length = \unpack('V', \stream_get_contents($res, 4))[1];

        if ($length > \fstat($res)['size'] || $length < 4) {
            throw new Exception('Wrong length');
        }

        return $res;
    }

    public static function tdesktop_read_bytearray($fp, bool $asString = false)
    {
        $length = Tools::unpackSignedInt(\strrev(\stream_get_contents($fp, 4)));
        $data = $length > 0 ? \stream_get_contents($fp, $length) : '';
        if ($asString) {
            return $data;
        }
        $res = \fopen('php://memory', 'rw+b');
        \fwrite($res, $data);
        \fseek($res, 0);

        return $res;
    }

    public static function tdesktop_decrypt($data, $auth_key)
    {
        $message_key = \stream_get_contents($data, 16);
        $encrypted_data = \stream_get_contents($data);

        [$aes_key, $aes_iv] = Crypt::oldKdf($message_key, $auth_key, false);
        $decrypted_data = Crypt::igeDecrypt($encrypted_data, $aes_key, $aes_iv);

        if ($message_key != \substr(\sha1($decrypted_data, true), 0, 16)) {
            throw new SecurityException('msg_key mismatch');
        }

        $res = \fopen('php://memory', 'rw+b');
        \fwrite($res, $decrypted_data);
        \fseek($res, 0);

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

    public static function tdesktop(string $session, string $new_session, $settings = [])
    {
        \set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
        $settings['old_session_key'] ??= 'data';
        $settings['old_session_passcode'] ??= '';

        if (\basename($session) !== 'tdata') {
            $session .= DIRECTORY_SEPARATOR.'tdata';
        }

        [$part_one_md5, $part_two_md5] = \str_split(self::tdesktop_md5($settings['old_session_key']), 16);
        self::$tdesktop_base_path = $session.DIRECTORY_SEPARATOR;
        self::$tdesktop_user_base_path = self::$tdesktop_base_path.$part_one_md5.DIRECTORY_SEPARATOR;

        $data = self::tdesktop_fopen('map');

        $salt = self::tdesktop_read_bytearray($data, true);
        $encryptedKey = self::tdesktop_read_bytearray($data);

        if (\strlen($salt)) {
            $keyIterCount = \strlen($settings['old_session_passcode']) ? 4000 : 4;
            $passKey = \openssl_pbkdf2($settings['old_session_passcode'], $salt, 256, $keyIterCount);

            self::$tdesktop_key = \stream_get_contents(
                self::tdesktop_read_bytearray(
                    self::tdesktop_decrypt($encryptedKey, $passKey),
                ),
            );
        } else {
            $key = 'key_'.$settings['old_session_key'];
            $data = self::tdesktop_fopen($key, self::FILEOPTION_SAFE);

            $salt = self::tdesktop_read_bytearray($data, true);
            if (\strlen($salt) !== 32) {
                throw new Exception('Length of salt is wrong!');
            }

            $encryptedKey = self::tdesktop_read_bytearray($data);
            $encryptedInfo = self::tdesktop_read_bytearray($data);

            $hash = \hash('sha512', $salt.$settings['old_session_passcode'].$salt, true);
            $iterCount = \strlen($settings['old_session_passcode']) ? 100000 : 1;

            $passKey = \openssl_pbkdf2($hash, $salt, 256, $iterCount, 'sha512');

            $key = self::tdesktop_read_bytearray(self::tdesktop_decrypt($encryptedKey, $passKey), true);
            $info = self::tdesktop_read_bytearray(self::tdesktop_decrypt($encryptedInfo, $key));

            self::$tdesktop_key = $key;

            $count = Tools::unpackSignedInt(\strrev(\stream_get_contents($info, 4)));
            Logger::log("Number of accounts: $count");
            for ($i = 0; $i != $count; $i++) {
                $idx = Tools::unpackSignedInt(\strrev(\stream_get_contents($info, 4)));
                if ($idx >= 0) {
                    $dataName = $settings['old_session_key'];
                    if ($idx > 0) {
                        $dataName = $settings['old_session_key'].'#'.($idx+1);
                    }
                    [$part_one_md5] = \str_split(self::tdesktop_md5($dataName), 16);
                }
            }
        }

        $main = self::tdesktop_fopen_encrypted($part_one_md5, self::FILEOPTION_SAFE);

        $auth_keys = [];

        while (!\feof($main)) {
            $magic = Tools::unpackSignedInt(\strrev(\stream_get_contents($main, 4)));
            switch ($magic) {
                case self::dbiDcOptionOldOld:
                    \stream_get_contents($main, 4);
                    self::tdesktop_read_bytearray($main);
                    self::tdesktop_read_bytearray($main);
                    \stream_get_contents($main, 4);
                    break;
                case self::dbiDcOptionOld:
                    \stream_get_contents($main, 8);
                    self::tdesktop_read_bytearray($main);
                    \stream_get_contents($main, 4);
                    break;
                case self::dbiDcOptions:
                    self::tdesktop_read_bytearray($main);
                    break;
                case self::dbiUser:
                    \stream_get_contents($main, 4);
                    $main_dc_id = Tools::unpackSignedInt(\strrev(\stream_get_contents($main, 4)));
                    break;
                case self::dbiKey:
                    $auth_keys[Tools::unpackSignedInt(\strrev(\stream_get_contents($main, 4)))] = \stream_get_contents($main, 256);
                    break;
                case self::dbiMtpAuthorization:
                    $main = self::tdesktop_read_bytearray($main);
                    //stream_get_contents($main, 4);
                    $user_id = Tools::unpackSignedInt(\strrev(\stream_get_contents($main, 4)));
                    $main_dc_id = Tools::unpackSignedInt(\strrev(\stream_get_contents($main, 4)));
                    $length = Tools::unpackSignedInt(\strrev(\stream_get_contents($main, 4)));
                    for ($x = 0; $x < $length; $x++) {
                        $dc = Tools::unpackSignedInt(\strrev(\stream_get_contents($main, 4)));
                        $auth_key = \stream_get_contents($main, 256);
                        if ($dc <= 5) {
                            $auth_keys[$dc] = $auth_key;
                        }
                    }
                    break 2;
                case self::dbiAutoDownload:
                    \stream_get_contents($main, 12);
                    break;
                case self::dbiDialogsMode:
                    \stream_get_contents($main, 8);
                    break;
                case self::dbiAuthSessionSettings:
                    self::tdesktop_read_bytearray($main);
                    break;
                case self::dbiConnectionTypeOld:
                    switch (Tools::unpackSignedInt(\strrev(\stream_get_contents($main, 4)))) {
                        case 2:
                        case 3:
                            self::tdesktop_read_bytearray($main);
                            \stream_get_contents($main, 4);
                            self::tdesktop_read_bytearray($main);
                            self::tdesktop_read_bytearray($main);
                            break;
                    }
                    break;
                case self::dbiConnectionType:
                    \stream_get_contents($main, 8);
                    self::tdesktop_read_bytearray($main);
                    \stream_get_contents($main, 4);
                    self::tdesktop_read_bytearray($main);
                    self::tdesktop_read_bytearray($main);
                    break;
                case self::dbiThemeKey:
                case self::dbiLangPackKey:
                case self::dbiMutePeer:
                    \stream_get_contents($main, 8);
                    break;
                case self::dbiWindowPosition:
                    \stream_get_contents($main, 24);
                    break;
                case self::dbiLoggedPhoneNumber:
                    self::tdesktop_read_bytearray($main);
                    break;
                case self::dbiMutedPeers:
                    $length = Tools::unpackSignedInt(\strrev(\stream_get_contents($main, 4)));
                    for ($x = 0; $x < $length; $x++) {
                        \stream_get_contents($main, 8);
                    }
                    // no break
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
                    throw new \Exception("Unknown type $magic");
                    break;
            }
        }

        return self::importAuthorization($auth_keys, $main_dc_id, $new_session, $settings);
    }
}
