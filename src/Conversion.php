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
     * Import authorization from raw auth key and DC id.
     *
     * @param array<int, string> $authorization Authorization info, DC ID => auth key
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
        /** @var APIWrapper */
        $wrapper = Tools::getVar($MadelineProto, 'wrapper');
        $wrapper->getAPI()->methodCallAsyncRead('help.getConfig', [], $main_dc_id);
        $MadelineProto->logger("About to import auth to DC $main_dc_id!", Logger::FATAL_ERROR);
        $MadelineProto->importAuthorization($authorization, $main_dc_id);
        return $MadelineProto;
    }
    /**
     * Convert telethon session.
     *
     * @param string $session Telethon session file
     * @param string $new_session MadelineProto session directory to create
     * @param SettingsAbstract|null $settings Settings
     */
    public static function telethon(string $session, string $new_session, ?SettingsAbstract $settings = null): API
    {
        if (!\extension_loaded('sqlite3')) {
            throw Exception::extension('sqlite3');
        }
        Magic::start(light: false);
        if (!isset(pathinfo($session)['extension'])) {
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

    /**
     * Convert pyrogram session.
     *
     * @param string $session Pyrogram session file
     * @param string $new_session MadelineProto session directory to create
     * @param SettingsAbstract|null $settings Settings
     */
    public static function pyrogram(string $session, string $new_session, ?SettingsAbstract $settings = null): API
    {
        set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
        if (!\extension_loaded('sqlite3')) {
            throw Exception::extension('sqlite3');
        }
        Magic::start(light: false);
        if (!isset(pathinfo($session)['extension'])) {
            $session .= '.session';
        }
        $session = Tools::absolute($session);
        $sqlite = new PDO("sqlite:$session");
        $session = $sqlite->query("SELECT * FROM sessions")->fetchAll(PDO::FETCH_ASSOC)[0];

        $settingsFull = new Settings;
        if ($settings) {
            $settingsFull->merge($settings);
        }
        $settingsFull->getConnection()->setTestMode((bool) $session['test_mode']);

        return self::importAuthorization([$session['dc_id'] => $session['auth_key']], $session['dc_id'], $new_session, $settings);
    }

    public static function zerobias(string|array $session, string $new_session, ?SettingsAbstract $settings = null): API
    {
        set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
        if (\is_string($session)) {
            $session = json_decode($session, true);
        }
        $dc = $session['dc'];
        $key = hex2bin($session["dc$dc".'_auth_key']);

        Assert::integer($dc);
        Assert::string($key);
        return self::importAuthorization([$dc => $key], $dc, $new_session, $settings);
    }

    private static function tdesktop_md5($data)
    {
        $result = '';
        foreach (str_split(md5($data), 2) as $byte) {
            $result .= strrev($byte);
        }

        return strtoupper($result);
    }

    private const FILEOPTION_SAFE = 1;
    private const FILEOPTION_USER = 2;
    public static $tdesktop_base_path;
    public static $tdesktop_user_base_path;
    public static $tdesktop_key;

    private static function tdesktop_fopen($fileName, $options = self::FILEOPTION_SAFE|self::FILEOPTION_USER)
    {
        $name = ($options & self::FILEOPTION_USER ? self::$tdesktop_user_base_path : self::$tdesktop_base_path).$fileName;
        $totry = [];
        foreach (['0', '1', 's'] as $x) {
            if (file_exists($name.$x)) {
                $totry[] = fopen($name.$x, 'rb');
            }
        }
        foreach ($totry as $fp) {
            if (stream_get_contents($fp, 4) !== 'TDF$') {
                Logger::log('Wrong magic', Logger::ERROR);
                continue;
            }
            $versionBytes = stream_get_contents($fp, 4);
            $version = Tools::unpackSignedInt($versionBytes);
            Logger::log("TDesktop version: $version");
            $data = stream_get_contents($fp);
            $md5 = substr($data, -16);
            $data = substr($data, 0, -16);

            $length = pack('l', \strlen($data));
            $length = Magic::$BIG_ENDIAN ? strrev($length) : $length;

            if (md5($data.$length.$versionBytes.'TDF$', true) !== $md5) {
                Logger::log('Wrong MD5', Logger::ERROR);
            }

            $res = fopen('php://memory', 'rw+b');
            fwrite($res, $data);
            fseek($res, 0);

            return $res;
        }

        throw new Exception("Could not open $fileName");
    }

    private static function tdesktop_fopen_encrypted($fileName, $options = 3)
    {
        $f = self::tdesktop_fopen($fileName, $options);
        $data = self::tdesktop_read_bytearray($f);
        $res = self::tdesktop_decrypt($data, self::$tdesktop_key);
        $length = unpack('V', stream_get_contents($res, 4))[1];

        if ($length > fstat($res)['size'] || $length < 4) {
            throw new Exception('Wrong length');
        }

        return $res;
    }

    private static function tdesktop_read_bytearray($fp, bool $asString = false)
    {
        $length = Tools::unpackSignedInt(strrev(stream_get_contents($fp, 4)));
        $data = $length > 0 ? stream_get_contents($fp, $length) : '';
        if ($asString) {
            return $data;
        }
        $res = fopen('php://memory', 'rw+b');
        fwrite($res, $data);
        fseek($res, 0);

        return $res;
    }

    private static function tdesktop_decrypt($data, $auth_key)
    {
        $message_key = stream_get_contents($data, 16);
        $encrypted_data = stream_get_contents($data);

        [$aes_key, $aes_iv] = Crypt::oldKdf($message_key, $auth_key, false);
        $decrypted_data = Crypt::igeDecrypt($encrypted_data, $aes_key, $aes_iv);

        if ($message_key != substr(sha1($decrypted_data, true), 0, 16)) {
            throw new SecurityException('msg_key mismatch');
        }

        $res = fopen('php://memory', 'rw+b');
        fwrite($res, $decrypted_data);
        fseek($res, 0);

        return $res;
    }

    private const dbiKey = 0x00;
    private const dbiUser = 0x01;
    private const dbiDcOptionOldOld = 0x02;
    private const dbiChatSizeMax = 0x03;
    private const dbiMutePeer = 0x04;
    private const dbiSendKey = 0x05;
    private const dbiAutoStart = 0x06;
    private const dbiStartMinimized = 0x07;
    private const dbiSoundNotify = 0x08;
    private const dbiWorkMode = 0x09;
    private const dbiSeenTrayTooltip = 0x0a;
    private const dbiDesktopNotify = 0x0b;
    private const dbiAutoUpdate = 0x0c;
    private const dbiLastUpdateCheck = 0x0d;
    private const dbiWindowPosition = 0x0e;
    private const dbiConnectionTypeOld = 0x0f;
    // 0x10 reserved
    private const dbiDefaultAttach = 0x11;
    private const dbiCatsAndDogs = 0x12;
    private const dbiReplaceEmojis = 0x13;
    private const dbiAskDownloadPath = 0x14;
    private const dbiDownloadPathOld = 0x15;
    private const dbiScale = 0x16;
    private const dbiEmojiTabOld = 0x17;
    private const dbiRecentEmojiOldOld = 0x18;
    private const dbiLoggedPhoneNumber = 0x19;
    private const dbiMutedPeers = 0x1a;
    // 0x1b reserved
    private const dbiNotifyView = 0x1c;
    private const dbiSendToMenu = 0x1d;
    private const dbiCompressPastedImage = 0x1e;
    private const dbiLangOld = 0x1f;
    private const dbiLangFileOld = 0x20;
    private const dbiTileBackground = 0x21;
    private const dbiAutoLock = 0x22;
    private const dbiDialogLastPath = 0x23;
    private const dbiRecentEmojiOld = 0x24;
    private const dbiEmojiVariantsOld = 0x25;
    private const dbiRecentStickers = 0x26;
    private const dbiDcOptionOld = 0x27;
    private const dbiTryIPv6 = 0x28;
    private const dbiSongVolume = 0x29;
    private const dbiWindowsNotificationsOld = 0x30;
    private const dbiIncludeMuted = 0x31;
    private const dbiMegagroupSizeMax = 0x32;
    private const dbiDownloadPath = 0x33;
    private const dbiAutoDownload = 0x34;
    private const dbiSavedGifsLimit = 0x35;
    private const dbiShowingSavedGifsOld = 0x36;
    private const dbiAutoPlay = 0x37;
    private const dbiAdaptiveForWide = 0x38;
    private const dbiHiddenPinnedMessages = 0x39;
    private const dbiRecentEmoji = 0x3a;
    private const dbiEmojiVariants = 0x3b;
    private const dbiDialogsMode = 0x40;
    private const dbiModerateMode = 0x41;
    private const dbiVideoVolume = 0x42;
    private const dbiStickersRecentLimit = 0x43;
    private const dbiNativeNotifications = 0x44;
    private const dbiNotificationsCount = 0x45;
    private const dbiNotificationsCorner = 0x46;
    private const dbiThemeKey = 0x47;
    private const dbiDialogsWidthRatioOld = 0x48;
    private const dbiUseExternalVideoPlayer = 0x49;
    private const dbiDcOptions = 0x4a;
    private const dbiMtpAuthorization = 0x4b;
    private const dbiLastSeenWarningSeenOld = 0x4c;
    private const dbiAuthSessionSettings = 0x4d;
    private const dbiLangPackKey = 0x4e;
    private const dbiConnectionType = 0x4f;
    private const dbiStickersFavedLimit = 0x50;
    private const dbiSuggestStickersByEmoji = 0x51;

    private const dbiEncryptedWithSalt = 333;
    private const dbiEncrypted = 444;

    // 500-600 reserved

    private const dbiVersion = 666;

    private static function tdesktop(string $session, string $new_session, $settings = [])
    {
        set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
        $settings['old_session_key'] ??= 'data';
        $settings['old_session_passcode'] ??= '';

        if (basename($session) !== 'tdata') {
            $session .= DIRECTORY_SEPARATOR.'tdata';
        }

        [$part_one_md5, $part_two_md5] = str_split(self::tdesktop_md5($settings['old_session_key']), 16);
        self::$tdesktop_base_path = $session.DIRECTORY_SEPARATOR;
        self::$tdesktop_user_base_path = self::$tdesktop_base_path.$part_one_md5.DIRECTORY_SEPARATOR;

        $data = self::tdesktop_fopen('map');

        $salt = self::tdesktop_read_bytearray($data, true);
        $encryptedKey = self::tdesktop_read_bytearray($data);

        if (\strlen($salt)) {
            $keyIterCount = \strlen($settings['old_session_passcode']) ? 4000 : 4;
            $passKey = openssl_pbkdf2($settings['old_session_passcode'], $salt, 256, $keyIterCount);

            self::$tdesktop_key = stream_get_contents(
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

            $hash = hash('sha512', $salt.$settings['old_session_passcode'].$salt, true);
            $iterCount = \strlen($settings['old_session_passcode']) ? 100000 : 1;

            $passKey = openssl_pbkdf2($hash, $salt, 256, $iterCount, 'sha512');

            $key = self::tdesktop_read_bytearray(self::tdesktop_decrypt($encryptedKey, $passKey), true);
            $info = self::tdesktop_read_bytearray(self::tdesktop_decrypt($encryptedInfo, $key));

            self::$tdesktop_key = $key;

            $count = Tools::unpackSignedInt(strrev(stream_get_contents($info, 4)));
            Logger::log("Number of accounts: $count");
            for ($i = 0; $i != $count; $i++) {
                $idx = Tools::unpackSignedInt(strrev(stream_get_contents($info, 4)));
                if ($idx >= 0) {
                    $dataName = $settings['old_session_key'];
                    if ($idx > 0) {
                        $dataName = $settings['old_session_key'].'#'.($idx+1);
                    }
                    [$part_one_md5] = str_split(self::tdesktop_md5($dataName), 16);
                }
            }
        }

        $main = self::tdesktop_fopen_encrypted($part_one_md5, self::FILEOPTION_SAFE);

        $auth_keys = [];

        while (!feof($main)) {
            $magic = Tools::unpackSignedInt(strrev(stream_get_contents($main, 4)));
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
                    $main_dc_id = Tools::unpackSignedInt(strrev(stream_get_contents($main, 4)));
                    break;
                case self::dbiKey:
                    $auth_keys[Tools::unpackSignedInt(strrev(stream_get_contents($main, 4)))] = stream_get_contents($main, 256);
                    break;
                case self::dbiMtpAuthorization:
                    $main = self::tdesktop_read_bytearray($main);
                    //stream_get_contents($main, 4);
                    $user_id = Tools::unpackSignedInt(strrev(stream_get_contents($main, 4)));
                    $main_dc_id = Tools::unpackSignedInt(strrev(stream_get_contents($main, 4)));
                    $length = Tools::unpackSignedInt(strrev(stream_get_contents($main, 4)));
                    for ($x = 0; $x < $length; $x++) {
                        $dc = Tools::unpackSignedInt(strrev(stream_get_contents($main, 4)));
                        $auth_key = stream_get_contents($main, 256);
                        if ($dc <= 5) {
                            $auth_keys[$dc] = $auth_key;
                        }
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
                    switch (Tools::unpackSignedInt(strrev(stream_get_contents($main, 4)))) {
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
                    $length = Tools::unpackSignedInt(strrev(stream_get_contents($main, 4)));
                    for ($x = 0; $x < $length; $x++) {
                        stream_get_contents($main, 8);
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
