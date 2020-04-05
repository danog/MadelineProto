<?php

/**
 * Serialization module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use function Amp\File\exists;
use function Amp\File\get;

/**
 * Manages serialization of the MadelineProto instance.
 */
class Serialization
{
    /**
     * List of session paths.
     *
     * @var array
     */
    private $paths = [];
    /**
     * Extract path components for serialization.
     *
     * @param string $file Session path
     *
     * @return array
     */
    public static function realpaths(string $file): array
    {
        $file = Tools::absolute($file);
        return ['file' => $file, 'lockfile' => $file.'.lock', 'tempfile' => $file.'.temp.session'];
    }
    /**
     * Unserialize legacy session.
     *
     * @param string $session Session name
     *
     * @internal
     *
     * @return \Generator
     */
    public static function legacyUnserialize(string $session): \Generator
    {
        $realpaths = self::realpaths($session);
        if (yield exists($realpaths['file'])) {
            Logger::log('Waiting for shared lock of serialization lockfile...');
            $unlock = yield Tools::flock($realpaths['lockfile'], LOCK_SH);
            Logger::log('Shared lock acquired, deserializing...');
            try {
                $tounserialize = yield get($realpaths['file']);
            } finally {
                $unlock();
            }
            Magic::classExists();
            try {
                $unserialized = \unserialize($tounserialize);
            } catch (\danog\MadelineProto\Bug74586Exception $e) {
                \class_exists('\\Volatile');
                $tounserialize = \str_replace('O:26:"danog\\MadelineProto\\Button":', 'O:35:"danog\\MadelineProto\\TL\\Types\\Button":', $tounserialize);
                foreach (['RSA', 'TL\\TLMethods', 'TL\\TLConstructors', 'MTProto', 'API', 'DataCenter', 'Connection', 'TL\\Types\\Button', 'TL\\Types\\Bytes', 'APIFactory'] as $class) {
                    \class_exists('\\danog\\MadelineProto\\'.$class);
                }
                $unserialized = \danog\Serialization::unserialize($tounserialize);
            } catch (\danog\MadelineProto\Exception $e) {
                if ($e->getFile() === 'MadelineProto' && $e->getLine() === 1) {
                    throw $e;
                }
                if (@\constant("MADELINEPROTO_TEST") === 'pony') {
                    throw $e;
                }
                \class_exists('\\Volatile');
                foreach (['RSA', 'TL\\TLMethods', 'TL\\TLConstructors', 'MTProto', 'API', 'DataCenter', 'Connection', 'TL\\Types\\Button', 'TL\\Types\\Bytes', 'APIFactory'] as $class) {
                    \class_exists('\\danog\\MadelineProto\\'.$class);
                }
                $changed = false;
                if (\strpos($tounserialize, 'O:26:"danog\\MadelineProto\\Button":') !== false) {
                    Logger::log("SUBBING BUTTONS!");
                    $tounserialize = \str_replace('O:26:"danog\\MadelineProto\\Button":', 'O:35:"danog\\MadelineProto\\TL\\Types\\Button":', $tounserialize);
                    $changed = true;
                }
                if (\strpos($e->getMessage(), "Erroneous data format for unserializing 'phpseclib\\Math\\BigInteger'") === 0) {
                    Logger::log("SUBBING BIGINTEGOR!");
                    $tounserialize = \str_replace('phpseclib\\Math\\BigInteger', 'phpseclib\\Math\\BigIntegor', $tounserialize);
                    $changed = true;
                }
                if (\strpos($tounserialize, 'C:25:"phpseclib\\Math\\BigInteger"') !== false) {
                    Logger::log("SUBBING TGSECLIB old!");
                    $tounserialize = \str_replace('C:25:"phpseclib\\Math\\BigInteger"', 'C:24:"tgseclib\\Math\\BigInteger"', $tounserialize);
                    $changed = true;
                }
                if (\strpos($tounserialize, 'C:26:"phpseclib3\\Math\\BigInteger"') !== false) {
                    Logger::log("SUBBING TGSECLIB!");
                    $tounserialize = \str_replace('C:26:"phpseclib3\\Math\\BigInteger"', 'C:24:"tgseclib\\Math\\BigInteger"', $tounserialize);
                    $changed = true;
                }
                Logger::log((string) $e, Logger::ERROR);
                if (!$changed) {
                    throw $e;
                }
                try {
                    $unserialized = \danog\Serialization::unserialize($tounserialize);
                } catch (\Throwable $e) {
                    $unserialized = \unserialize($tounserialize);
                }
            } catch (\Throwable $e) {
                Logger::log((string) $e, Logger::ERROR);
                throw $e;
            }
            if ($unserialized instanceof \danog\PlaceHolder) {
                $unserialized = \danog\Serialization::unserialize($tounserialize);
            }
            if ($unserialized === false) {
                throw new Exception(\danog\MadelineProto\Lang::$current_lang['deserialization_error']);
            }
            return $unserialized;
        }
    }
}
