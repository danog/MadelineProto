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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use danog\MadelineProto\MTProtoTools\Crypt;
use PDO;
use Webmozart\Assert\Assert;

use const DIRECTORY_SEPARATOR;

final class Conversion
{
    /**
     * Import authorization from raw auth key and DC id.
     *
     * @param array<int, string> $authorization Authorization info, DC ID => auth key
     */
    public static function importAuthorization(#[\SensitiveParameter] array $authorization, int $main_dc_id, string $session, ?SettingsAbstract $settings = null): API
    {
        $settingsFull = new Settings;
        if ($settings) {
            $settingsFull->merge($settings);
        }
        $settings = $settingsFull;
        $settings->getLogger()->setLevel(Logger::ULTRA_VERBOSE);
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
    public static function telethon(#[\SensitiveParameter] string $session, string $new_session, ?SettingsAbstract $settings = null): API
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
    public static function pyrogram(#[\SensitiveParameter] string $session, string $new_session, ?SettingsAbstract $settings = null): API
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

    public static function zerobias(#[\SensitiveParameter] string|array $session, string $new_session, ?SettingsAbstract $settings = null): API
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

    /**
     * Convert a Telegram Desktop session.
     *
     * Passcodes set by recent versions of Telegram Desktop are wrapped with argon2id or scrypt, which are not supported: turn off the local passcode before converting.
     *
     * @param string $session Telegram Desktop tdata directory (or its parent directory)
     * @param string $new_session MadelineProto session directory to create
     * @param SettingsAbstract|null $settings Settings
     * @param string $passcode Local passcode, if any
     * @param string $key Data name, if Telegram Desktop is started with -key
     * @param int|null $account Index of the account to convert, the active one by default
     */
    public static function tdesktop(string $session, string $new_session, ?SettingsAbstract $settings = null, #[\SensitiveParameter] string $passcode = '', string $key = 'data', ?int $account = null): API
    {
        $authorization = self::parseTdesktop($session, $passcode, $key, $account);
        return self::importAuthorization($authorization['auth_keys'], $authorization['main_dc_id'], $new_session, $settings);
    }

    private const TDESKTOP_SALT_SIZE = 32;
    private const TDESKTOP_KEY_DATA_MAGIC = 0x4B443200;
    private const TDESKTOP_KEY_DATA_FORMAT_VERSION = 2;

    private const dbiKey = 0x00;
    private const dbiUser = 0x01;
    private const dbiMtpAuthorization = 0x4b;

    /**
     * Parse the MTProto authorization of a Telegram Desktop session.
     *
     * @internal
     *
     * @param string $session Telegram Desktop tdata directory (or its parent directory)
     * @param string $passcode Local passcode, if any
     * @param string $key Data name, if Telegram Desktop is started with -key
     * @param int|null $account Index of the account to parse, the active one by default
     *
     * @return array{main_dc_id: int, user_id: int, auth_keys: array<int, string>}
     */
    public static function parseTdesktop(string $session, #[\SensitiveParameter] string $passcode = '', string $key = 'data', ?int $account = null): array
    {
        if (basename($session) !== 'tdata') {
            $session .= DIRECTORY_SEPARATOR.'tdata';
        }
        $base = $session.DIRECTORY_SEPARATOR;

        // storage_domain.cpp, Domain::startModern
        $keyData = self::tdesktopReadFile($base.'key_'.$key);
        if ($keyData !== null) {
            $offset = 0;
            $salt = self::tdesktopReadByteArray($keyData, $offset);
            $openKeyEncrypted = self::tdesktopReadByteArray($keyData, $offset);
            $infoEncrypted = self::tdesktopReadByteArray($keyData, $offset);
            if (\strlen($salt) !== self::TDESKTOP_SALT_SIZE) {
                throw new Exception('Wrong salt length in the key data!');
            }
            if ($offset === \strlen($keyData)) {
                // Legacy key data, wrapped with the passcode (if any)
                $localKey = self::tdesktopUnwrapLocalKey($openKeyEncrypted, self::tdesktopCreateLocalKey($passcode, $salt));
            } elseif ($passcode === '') {
                if ($openKeyEncrypted === '') {
                    throw new Exception('The Telegram Desktop session is protected by a local passcode!');
                }
                $localKey = self::tdesktopUnwrapLocalKey($openKeyEncrypted, self::tdesktopCreateLocalKey('', $salt));
            } else {
                $trailer = self::tdesktopReadByteArray($keyData, $offset);
                $offset = 0;
                $magic = self::tdesktopReadUint32($trailer, $offset);
                $formatVersion = self::tdesktopReadUint32($trailer, $offset);
                $committed = self::tdesktopReadUint32($trailer, $offset);
                $wrapCount = self::tdesktopReadUint32($trailer, $offset);
                if ($magic !== self::TDESKTOP_KEY_DATA_MAGIC || $formatVersion > self::TDESKTOP_KEY_DATA_FORMAT_VERSION) {
                    throw new Exception('Unsupported key data format!');
                }
                $wraps = [];
                for ($x = 0; $x < $wrapCount; $x++) {
                    $kind = self::tdesktopReadUint32($trailer, $offset);
                    $generation = self::tdesktopReadUint32($trailer, $offset);
                    $offset += 12; // KDF memory, time and parallelism
                    $wrapSalt = self::tdesktopReadByteArray($trailer, $offset);
                    $wrapKeyEncrypted = self::tdesktopReadByteArray($trailer, $offset);
                    // Only the wrap of the committed generation is live
                    if ($generation === $committed) {
                        $wraps[] = [$kind, $wrapSalt, $wrapKeyEncrypted];
                    }
                }
                if (\count($wraps) !== 1) {
                    throw new Exception('The Telegram Desktop session is not protected by a local passcode!');
                }
                [[$kind, $wrapSalt, $wrapKeyEncrypted]] = $wraps;
                if ($kind !== 0) {
                    throw new Exception('The local passcode of the Telegram Desktop session uses argon2id or scrypt, which are not supported: turn off the local passcode in Telegram Desktop before converting.');
                }
                $localKey = self::tdesktopUnwrapLocalKey($wrapKeyEncrypted, self::tdesktopCreateLocalKey($passcode, $wrapSalt));
            }

            $info = self::tdesktopDecrypt($infoEncrypted, $localKey);
            $offset = 0;
            $count = self::tdesktopReadInt32($info, $offset);
            $indexes = [];
            for ($x = 0; $x < $count; $x++) {
                $index = self::tdesktopReadInt32($info, $offset);
                if ($index >= 0) {
                    $indexes[$index] = true;
                }
            }
            if (!$indexes) {
                throw new Exception('No accounts in the Telegram Desktop session!');
            }
            if ($account === null) {
                $account = $offset < \strlen($info) ? self::tdesktopReadInt32($info, $offset) : -1;
                if (!isset($indexes[$account])) {
                    $account = array_key_first($indexes);
                }
            }
            if (!isset($indexes[$account])) {
                throw new Exception("No account with index $account in the Telegram Desktop session!");
            }
        } else {
            // Before multiple accounts: storage_account.cpp, Account::readMapWith
            if ($account) {
                throw new Exception("No account with index $account in the Telegram Desktop session!");
            }
            $account = 0;
            $map = self::tdesktopReadFile($base.self::tdesktopFilePart($key).DIRECTORY_SEPARATOR.'map');
            if ($map === null) {
                throw new Exception("Could not find a Telegram Desktop session in $session!");
            }
            $offset = 0;
            $salt = self::tdesktopReadByteArray($map, $offset);
            $keyEncrypted = self::tdesktopReadByteArray($map, $offset);
            if (\strlen($salt) !== self::TDESKTOP_SALT_SIZE) {
                throw new Exception('Wrong salt length in the map!');
            }
            $passKey = openssl_pbkdf2($passcode, $salt, 256, $passcode === '' ? 4 : 4000, 'sha1');
            $localKey = self::tdesktopUnwrapLocalKey($keyEncrypted, $passKey);
        }

        // main_account.cpp, ComposeDataString; storage_account.cpp, Account::readMtpData
        $dataName = str_replace('#', '', $key);
        if ($account > 0) {
            $dataName .= '#'.($account + 1);
        }
        $mtp = self::tdesktopReadFile($base.self::tdesktopFilePart($dataName));
        if ($mtp === null) {
            throw new Exception("Could not find the MTProto data of account $account!");
        }
        $offset = 0;
        $mtp = self::tdesktopDecrypt(self::tdesktopReadByteArray($mtp, $offset), $localKey);

        $authorization = null;
        $user_id = 0;
        $main_dc_id = 0;
        $auth_keys = [];
        $offset = 0;
        while ($offset < \strlen($mtp)) {
            $block = self::tdesktopReadUint32($mtp, $offset);
            switch ($block) {
                case self::dbiMtpAuthorization:
                    $authorization = self::tdesktopReadByteArray($mtp, $offset);
                    break;
                case self::dbiUser:
                    $user_id = self::tdesktopReadInt32($mtp, $offset);
                    $main_dc_id = self::tdesktopReadUint32($mtp, $offset);
                    break;
                case self::dbiKey:
                    $dc = self::tdesktopReadInt32($mtp, $offset);
                    $auth_keys[$dc] = self::tdesktopRead($mtp, $offset, 256);
                    break;
                default:
                    throw new Exception("Unknown block $block in the MTProto data!");
            }
        }
        if ($authorization !== null) {
            // main_account.cpp, Account::setMtpAuthorization
            $auth_keys = [];
            $offset = 0;
            $user_id = self::tdesktopReadInt32($authorization, $offset);
            $main_dc_id = self::tdesktopReadInt32($authorization, $offset);
            if ($user_id === -1 && $main_dc_id === -1) {
                // kWideIdsTag, followed by a 64-bit user ID
                $user_id = unpack('J', self::tdesktopRead($authorization, $offset, 8))[1];
                $main_dc_id = self::tdesktopReadInt32($authorization, $offset);
            }
            $count = self::tdesktopReadInt32($authorization, $offset);
            for ($x = 0; $x < $count; $x++) {
                $dc = self::tdesktopReadInt32($authorization, $offset);
                $auth_keys[$dc] = self::tdesktopRead($authorization, $offset, 256);
            }
        }
        if (!isset($auth_keys[$main_dc_id])) {
            throw new Exception("No auth key for the main DC $main_dc_id in the Telegram Desktop session!");
        }

        return ['main_dc_id' => $main_dc_id, 'user_id' => $user_id, 'auth_keys' => $auth_keys];
    }

    /**
     * File name of a key, as in tdesktop's ToFilePart: the first 8 bytes of the MD5 of the name, in hex, low nibble first.
     *
     * @psalm-pure
     */
    private static function tdesktopFilePart(string $name): string
    {
        return strtoupper(strrev(bin2hex(strrev(substr(md5($name, true), 0, 8)))));
    }

    /**
     * Read a file as tdesktop's ReadFile does, returning its data or null if no valid copy exists.
     */
    private static function tdesktopReadFile(string $name): ?string
    {
        if (file_exists($name.'s')) {
            $totry = [$name.'s'];
        } else {
            $totry = array_filter([$name.'0', $name.'1'], file_exists(...));
            usort($totry, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));
        }
        foreach ($totry as $file) {
            $contents = file_get_contents($file);
            if (\strlen($contents) < 24 || substr($contents, 0, 4) !== 'TDF$') {
                Logger::log("Wrong magic in $file", Logger::ERROR);
                continue;
            }
            $versionBytes = substr($contents, 4, 4);
            Logger::log('TDesktop version: '.unpack('V', $versionBytes)[1]);
            $data = substr($contents, 8, -16);
            if (md5($data.pack('V', \strlen($data)).$versionBytes.'TDF$', true) !== substr($contents, -16)) {
                Logger::log("Wrong MD5 in $file", Logger::ERROR);
                continue;
            }
            return $data;
        }
        return null;
    }

    private static function tdesktopRead(string $data, int &$offset, int $length): string
    {
        if ($length < 0 || $offset + $length > \strlen($data)) {
            throw new Exception('Unexpected end of Telegram Desktop data!');
        }
        $result = substr($data, $offset, $length);
        $offset += $length;
        return $result;
    }

    private static function tdesktopReadUint32(string $data, int &$offset): int
    {
        return unpack('N', self::tdesktopRead($data, $offset, 4))[1];
    }

    private static function tdesktopReadInt32(string $data, int &$offset): int
    {
        $value = self::tdesktopReadUint32($data, $offset);
        return $value >= 0x80000000 ? $value - 0x100000000 : $value;
    }

    /**
     * Read a QByteArray (a null one is empty).
     */
    private static function tdesktopReadByteArray(string $data, int &$offset): string
    {
        $length = self::tdesktopReadUint32($data, $offset);
        return $length === 0xFFFFFFFF ? '' : self::tdesktopRead($data, $offset, $length);
    }

    /**
     * Decrypt data encrypted with a local key, as tdesktop's DecryptLocal does.
     */
    private static function tdesktopDecrypt(string $encrypted, #[\SensitiveParameter] string $key): string
    {
        if (\strlen($encrypted) <= 16 || \strlen($encrypted) % 16) {
            throw new Exception('Wrong length of Telegram Desktop encrypted data!');
        }
        $message_key = substr($encrypted, 0, 16);
        [$aes_key, $aes_iv] = Crypt::oldKdf($message_key, $key, false);
        $decrypted = Crypt::igeDecrypt(substr($encrypted, 16), $aes_key, $aes_iv);
        if ($message_key !== substr(sha1($decrypted, true), 0, 16)) {
            throw new SecurityException('msg_key mismatch: wrong passcode?');
        }
        $length = unpack('V', $decrypted)[1];
        if ($length > \strlen($decrypted) || $length <= \strlen($decrypted) - 16 || $length < 4) {
            throw new Exception('Wrong length of Telegram Desktop decrypted data!');
        }
        return substr($decrypted, 4, $length - 4);
    }

    /**
     * Local key from a passcode, as tdesktop's CreateLocalKey does.
     */
    private static function tdesktopCreateLocalKey(string $passcode, string $salt): string
    {
        $hash = hash('sha512', $salt.$passcode.$salt, true);
        return openssl_pbkdf2($hash, $salt, 256, $passcode === '' ? 1 : 100000, 'sha512');
    }

    private static function tdesktopUnwrapLocalKey(string $encrypted, string $wrapKey): string
    {
        $key = self::tdesktopDecrypt($encrypted, $wrapKey);
        if (\strlen($key) !== 256) {
            throw new Exception('Wrong length of the Telegram Desktop local key!');
        }
        return $key;
    }
}
