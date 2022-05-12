<?php

/**
 * Magic module.
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

use Amp\Deferred;
use Amp\Loop;
use Amp\Loop\Driver;
use danog\MadelineProto\TL\Conversion\Extension;
use ReflectionClass;
use function Amp\ByteStream\getStdin;
use function Amp\File\read;
use function Amp\Log\hasColorSupport;
use function Amp\Promise\wait;

class Magic
{
    const ZERO_CHANNEL_ID = -1000000000000;
    /**
     * Static storage.
     *
     * @var array
     */
    public static $storage = [];
    /**
     * Whether this system is bigendian.
     *
     * @var boolean
     */
    public static $BIG_ENDIAN = false;
    /**
     * Whether this is a TTY console.
     *
     * @var boolean
     */
    public static $isatty = false;
    /**
     * Whether we're in a fork.
     *
     * @var boolean
     */
    public static $isFork = false;
    /**
     * Whether this is an IPC worker.
     */
    public static bool $isIpcWorker = false;
    /**
     * Whether we can get our PID.
     *
     * @var boolean
     */
    public static $can_getmypid = true;
    /**
     * Whether we can amphp/parallel.
     *
     * @var boolean
     */
    public static $can_parallel = false;
    /**
     * Whether we can get our CWD.
     *
     * @var boolean
     */
    public static $can_getcwd = false;
    /**
     * Whether we've processed forks.
     *
     * @var boolean
     */
    public static $processed_fork = false;
    /**
     * Whether we can use ipv6.
     *
     * @var bool
     */
    public static $ipv6 = false;
    /**
     * Our PID.
     *
     * @var int
     */
    public static $pid;
    /**
     * Whether we've inited all light constants.
     *
     * @var boolean
     */
    private static $initedLight = false;
    /**
     * Whether we've inited all static constants.
     *
     * @var boolean
     */
    private static $inited = false;
    /**
     * Whether we've inited the ipv6 property.
     *
     * @var boolean
     */
    private static $initedIpv6 = false;
    /**
     * Bigint zero.
     *
     * @var \phpseclib3\Math\BigInteger
     */
    public static $zero;
    /**
     * Bigint one.
     *
     * @var \phpseclib3\Math\BigInteger
     */
    public static $one;
    /**
     * Bigint two.
     *
     * @var \phpseclib3\Math\BigInteger
     */
    public static $two;
    /**
     * Bigint 2^1984.
     *
     * @var \phpseclib3\Math\BigInteger
     */
    public static $twoe1984;
    /**
     * Bigint 2^2047.
     *
     * @var \phpseclib3\Math\BigInteger
     */
    public static $twoe2047;
    /**
     * Bigint 2^2048.
     *
     * @var \phpseclib3\Math\BigInteger
     */
    public static $twoe2048;
    /**
     * Decoded UTF8 emojis for call fingerprint.
     *
     * @var array
     */
    public static $emojis;
    /**
     * MadelineProto revision.
     *
     * @var string
     */
    public static $revision;
    /**
     * MadelineProto version (clean).
     *
     * @var string
     */
    public static $version;
    /**
     * Latest MadelineProto version.
     *
     * @var string
     */
    public static $version_latest;
    /**
     * Our CWD.
     *
     * @var string
     */
    public static $cwd;
    /**
     * Caller script CWD.
     *
     * @var string
     */
    public static $script_cwd;
    /**
     * Whether we're running on altervista.
     *
     * @var boolean
     */
    public static $altervista = false;
    /**
     * Wether we're running on 000webhost (yuck).
     *
     * @var boolean
     */
    public static $zerowebhost = false;
    /**
     * Whether a signal was sent to the processand the system must shut down.
     *
     * @var boolean
     */
    public static $signaled = false;
    /**
     * Whether to suspend certain stdout log printing, when reading input.
     *
     * @var ?Deferred
     */
    public static $suspendPeriodicLogging;
    /**
     * All mime types.
     *
     * @var array<string, string>
     */
    public static $allMimes = [];
    /**
     * Whether the openssl extension is loaded.
     *
     * @var boolean
     */
    public static bool $hasOpenssl = false;
    /**
     * Encoded emojis.
     *
     * @var string
     */
    const JSON_EMOJIS = '["\\ud83d\\ude09","\\ud83d\\ude0d","\\ud83d\\ude1b","\\ud83d\\ude2d","\\ud83d\\ude31","\\ud83d\\ude21","\\ud83d\\ude0e","\\ud83d\\ude34","\\ud83d\\ude35","\\ud83d\\ude08","\\ud83d\\ude2c","\\ud83d\\ude07","\\ud83d\\ude0f","\\ud83d\\udc6e","\\ud83d\\udc77","\\ud83d\\udc82","\\ud83d\\udc76","\\ud83d\\udc68","\\ud83d\\udc69","\\ud83d\\udc74","\\ud83d\\udc75","\\ud83d\\ude3b","\\ud83d\\ude3d","\\ud83d\\ude40","\\ud83d\\udc7a","\\ud83d\\ude48","\\ud83d\\ude49","\\ud83d\\ude4a","\\ud83d\\udc80","\\ud83d\\udc7d","\\ud83d\\udca9","\\ud83d\\udd25","\\ud83d\\udca5","\\ud83d\\udca4","\\ud83d\\udc42","\\ud83d\\udc40","\\ud83d\\udc43","\\ud83d\\udc45","\\ud83d\\udc44","\\ud83d\\udc4d","\\ud83d\\udc4e","\\ud83d\\udc4c","\\ud83d\\udc4a","\\u270c","\\u270b","\\ud83d\\udc50","\\ud83d\\udc46","\\ud83d\\udc47","\\ud83d\\udc49","\\ud83d\\udc48","\\ud83d\\ude4f","\\ud83d\\udc4f","\\ud83d\\udcaa","\\ud83d\\udeb6","\\ud83c\\udfc3","\\ud83d\\udc83","\\ud83d\\udc6b","\\ud83d\\udc6a","\\ud83d\\udc6c","\\ud83d\\udc6d","\\ud83d\\udc85","\\ud83c\\udfa9","\\ud83d\\udc51","\\ud83d\\udc52","\\ud83d\\udc5f","\\ud83d\\udc5e","\\ud83d\\udc60","\\ud83d\\udc55","\\ud83d\\udc57","\\ud83d\\udc56","\\ud83d\\udc59","\\ud83d\\udc5c","\\ud83d\\udc53","\\ud83c\\udf80","\\ud83d\\udc84","\\ud83d\\udc9b","\\ud83d\\udc99","\\ud83d\\udc9c","\\ud83d\\udc9a","\\ud83d\\udc8d","\\ud83d\\udc8e","\\ud83d\\udc36","\\ud83d\\udc3a","\\ud83d\\udc31","\\ud83d\\udc2d","\\ud83d\\udc39","\\ud83d\\udc30","\\ud83d\\udc38","\\ud83d\\udc2f","\\ud83d\\udc28","\\ud83d\\udc3b","\\ud83d\\udc37","\\ud83d\\udc2e","\\ud83d\\udc17","\\ud83d\\udc34","\\ud83d\\udc11","\\ud83d\\udc18","\\ud83d\\udc3c","\\ud83d\\udc27","\\ud83d\\udc25","\\ud83d\\udc14","\\ud83d\\udc0d","\\ud83d\\udc22","\\ud83d\\udc1b","\\ud83d\\udc1d","\\ud83d\\udc1c","\\ud83d\\udc1e","\\ud83d\\udc0c","\\ud83d\\udc19","\\ud83d\\udc1a","\\ud83d\\udc1f","\\ud83d\\udc2c","\\ud83d\\udc0b","\\ud83d\\udc10","\\ud83d\\udc0a","\\ud83d\\udc2b","\\ud83c\\udf40","\\ud83c\\udf39","\\ud83c\\udf3b","\\ud83c\\udf41","\\ud83c\\udf3e","\\ud83c\\udf44","\\ud83c\\udf35","\\ud83c\\udf34","\\ud83c\\udf33","\\ud83c\\udf1e","\\ud83c\\udf1a","\\ud83c\\udf19","\\ud83c\\udf0e","\\ud83c\\udf0b","\\u26a1","\\u2614","\\u2744","\\u26c4","\\ud83c\\udf00","\\ud83c\\udf08","\\ud83c\\udf0a","\\ud83c\\udf93","\\ud83c\\udf86","\\ud83c\\udf83","\\ud83d\\udc7b","\\ud83c\\udf85","\\ud83c\\udf84","\\ud83c\\udf81","\\ud83c\\udf88","\\ud83d\\udd2e","\\ud83c\\udfa5","\\ud83d\\udcf7","\\ud83d\\udcbf","\\ud83d\\udcbb","\\u260e","\\ud83d\\udce1","\\ud83d\\udcfa","\\ud83d\\udcfb","\\ud83d\\udd09","\\ud83d\\udd14","\\u23f3","\\u23f0","\\u231a","\\ud83d\\udd12","\\ud83d\\udd11","\\ud83d\\udd0e","\\ud83d\\udca1","\\ud83d\\udd26","\\ud83d\\udd0c","\\ud83d\\udd0b","\\ud83d\\udebf","\\ud83d\\udebd","\\ud83d\\udd27","\\ud83d\\udd28","\\ud83d\\udeaa","\\ud83d\\udeac","\\ud83d\\udca3","\\ud83d\\udd2b","\\ud83d\\udd2a","\\ud83d\\udc8a","\\ud83d\\udc89","\\ud83d\\udcb0","\\ud83d\\udcb5","\\ud83d\\udcb3","\\u2709","\\ud83d\\udceb","\\ud83d\\udce6","\\ud83d\\udcc5","\\ud83d\\udcc1","\\u2702","\\ud83d\\udccc","\\ud83d\\udcce","\\u2712","\\u270f","\\ud83d\\udcd0","\\ud83d\\udcda","\\ud83d\\udd2c","\\ud83d\\udd2d","\\ud83c\\udfa8","\\ud83c\\udfac","\\ud83c\\udfa4","\\ud83c\\udfa7","\\ud83c\\udfb5","\\ud83c\\udfb9","\\ud83c\\udfbb","\\ud83c\\udfba","\\ud83c\\udfb8","\\ud83d\\udc7e","\\ud83c\\udfae","\\ud83c\\udccf","\\ud83c\\udfb2","\\ud83c\\udfaf","\\ud83c\\udfc8","\\ud83c\\udfc0","\\u26bd","\\u26be","\\ud83c\\udfbe","\\ud83c\\udfb1","\\ud83c\\udfc9","\\ud83c\\udfb3","\\ud83c\\udfc1","\\ud83c\\udfc7","\\ud83c\\udfc6","\\ud83c\\udfca","\\ud83c\\udfc4","\\u2615","\\ud83c\\udf7c","\\ud83c\\udf7a","\\ud83c\\udf77","\\ud83c\\udf74","\\ud83c\\udf55","\\ud83c\\udf54","\\ud83c\\udf5f","\\ud83c\\udf57","\\ud83c\\udf71","\\ud83c\\udf5a","\\ud83c\\udf5c","\\ud83c\\udf61","\\ud83c\\udf73","\\ud83c\\udf5e","\\ud83c\\udf69","\\ud83c\\udf66","\\ud83c\\udf82","\\ud83c\\udf70","\\ud83c\\udf6a","\\ud83c\\udf6b","\\ud83c\\udf6d","\\ud83c\\udf6f","\\ud83c\\udf4e","\\ud83c\\udf4f","\\ud83c\\udf4a","\\ud83c\\udf4b","\\ud83c\\udf52","\\ud83c\\udf47","\\ud83c\\udf49","\\ud83c\\udf53","\\ud83c\\udf51","\\ud83c\\udf4c","\\ud83c\\udf50","\\ud83c\\udf4d","\\ud83c\\udf46","\\ud83c\\udf45","\\ud83c\\udf3d","\\ud83c\\udfe1","\\ud83c\\udfe5","\\ud83c\\udfe6","\\u26ea","\\ud83c\\udff0","\\u26fa","\\ud83c\\udfed","\\ud83d\\uddfb","\\ud83d\\uddfd","\\ud83c\\udfa0","\\ud83c\\udfa1","\\u26f2","\\ud83c\\udfa2","\\ud83d\\udea2","\\ud83d\\udea4","\\u2693","\\ud83d\\ude80","\\u2708","\\ud83d\\ude81","\\ud83d\\ude82","\\ud83d\\ude8b","\\ud83d\\ude8e","\\ud83d\\ude8c","\\ud83d\\ude99","\\ud83d\\ude97","\\ud83d\\ude95","\\ud83d\\ude9b","\\ud83d\\udea8","\\ud83d\\ude94","\\ud83d\\ude92","\\ud83d\\ude91","\\ud83d\\udeb2","\\ud83d\\udea0","\\ud83d\\ude9c","\\ud83d\\udea6","\\u26a0","\\ud83d\\udea7","\\u26fd","\\ud83c\\udfb0","\\ud83d\\uddff","\\ud83c\\udfaa","\\ud83c\\udfad","\\ud83c\\uddef\\ud83c\\uddf5","\\ud83c\\uddf0\\ud83c\\uddf7","\\ud83c\\udde9\\ud83c\\uddea","\\ud83c\\udde8\\ud83c\\uddf3","\\ud83c\\uddfa\\ud83c\\uddf8","\\ud83c\\uddeb\\ud83c\\uddf7","\\ud83c\\uddea\\ud83c\\uddf8","\\ud83c\\uddee\\ud83c\\uddf9","\\ud83c\\uddf7\\ud83c\\uddfa","\\ud83c\\uddec\\ud83c\\udde7","1\\u20e3","2\\u20e3","3\\u20e3","4\\u20e3","5\\u20e3","6\\u20e3","7\\u20e3","8\\u20e3","9\\u20e3","0\\u20e3","\\ud83d\\udd1f","\\u2757","\\u2753","\\u2665","\\u2666","\\ud83d\\udcaf","\\ud83d\\udd17","\\ud83d\\udd31","\\ud83d\\udd34","\\ud83d\\udd35","\\ud83d\\udd36","\\ud83d\\udd37"]';
    /**
     * Initialize magic constants.
     *
     * @param bool $light Use lightweight initialization routine
     *
     * @return void
     */
    public static function start(bool $light = false): void
    {
        if (self::$inited || (self::$initedLight && $light)) {
            return;
        }
        if (PHP_INT_SIZE < 8) {
            throw new \danog\MadelineProto\Exception('A 64-bit build of PHP is required to run MadelineProto, PHP 8.0+ recommended.', 0, null, 'MadelineProto', 1);
        }
        if (!self::$initedLight) {
            // Setup error reporting
            \set_error_handler([Exception::class, 'ExceptionErrorHandler']);
            \set_exception_handler([Exception::class, 'ExceptionHandler']);
            self::$isIpcWorker = \defined('MADELINE_WORKER_TYPE') ? \MADELINE_WORKER_TYPE === 'madeline-ipc' : false;
            // Important, obtain root relative to caller script
            $backtrace = \debug_backtrace(0);
            self::$script_cwd = self::$cwd = \dirname(\end($backtrace)['file']);
            if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
                try {
                    \error_reporting(E_ALL);
                    \ini_set('log_errors', 1);
                    \ini_set('error_log', Magic::$script_cwd.DIRECTORY_SEPARATOR.'MadelineProto.log');
                    \error_log('Enabled PHP logging');
                } catch (\Throwable $e) {
                    //$this->logger->logger('Could not enable PHP logging');
                }
            }
            try {
                \ini_set('memory_limit', -1);
            } catch (\Throwable $e) {
            }
            // Check if we're in a console, for colorful log output
            try {
                self::$isatty = \defined('STDOUT') && hasColorSupport();
            } catch (\Throwable $e) {
            }
            try {
                self::$cwd = \getcwd();
                self::$can_getcwd = true;
            } catch (\Throwable $e) {
            }
            try {
                if (\function_exists('set_time_limit')) {
                    \set_time_limit(-1);
                }
            } catch (\Throwable $e) {
            }
            // Define signal handlers
            if (\defined('SIGINT')) {
                //if (function_exists('pcntl_async_signals')) pcntl_async_signals(true);
                try {
                    \pcntl_signal(SIGINT, fn () => null);
                    \pcntl_signal(SIGINT, SIG_DFL);
                    Loop::unreference(Loop::onSignal(SIGINT, static function () {
                        Logger::log('Got sigint', Logger::FATAL_ERROR);
                        Magic::shutdown(self::$isIpcWorker ? 0 : 1);
                    }));
                    Loop::unreference(Loop::onSignal(SIGTERM, static function () {
                        Logger::log('Got sigterm', Logger::FATAL_ERROR);
                        Magic::shutdown(self::$isIpcWorker ? 0 : 1);
                    }));
                } catch (\Throwable $e) {
                }
            }
            self::$altervista = isset($_SERVER['SERVER_ADMIN']) && \strpos($_SERVER['SERVER_ADMIN'], 'altervista.org');
            self::$zerowebhost = isset($_SERVER['SERVER_ADMIN']) && \strpos($_SERVER['SERVER_ADMIN'], '000webhost.io');
            self::$initedLight = true;
            if ($light) {
                if (!\defined('AMP_WORKER')) {
                    \define('AMP_WORKER', true);
                }
                return;
            }
        }
        foreach (['xml', 'fileinfo', 'json', 'mbstring'] as $extension) {
            if (!\extension_loaded($extension)) {
                throw Exception::extension($extension);
            }
        }
        self::$BIG_ENDIAN = \pack('L', 1) === \pack('N', 1);
        if (\class_exists('\\danog\\MadelineProto\\VoIP')) {
            if (!\defined('\\danog\\MadelineProto\\VoIP::PHP_LIBTGVOIP_VERSION') || !\in_array(\danog\MadelineProto\VoIP::PHP_LIBTGVOIP_VERSION, ['1.5.0'])) {
                throw new \danog\MadelineProto\Exception(\hex2bin(\danog\MadelineProto\Lang::$current_lang['v_tgerror']), 0, null, 'MadelineProto', 1);
            }
        }
        self::$hasOpenssl = \extension_loaded('openssl');
        self::$emojis = \json_decode(self::JSON_EMOJIS);
        self::$zero = new \phpseclib3\Math\BigInteger(0);
        self::$one = new \phpseclib3\Math\BigInteger(1);
        self::$two = new \phpseclib3\Math\BigInteger(2);
        self::$twoe1984 = new \phpseclib3\Math\BigInteger('010000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 16);
        self::$twoe2047 = new \phpseclib3\Math\BigInteger('80000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 16);
        self::$twoe2048 = new \phpseclib3\Math\BigInteger('0100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 16);
        self::$can_getmypid = !self::$altervista && !self::$zerowebhost;
        if (\file_exists(__DIR__.'/../../../.git/refs/heads/master')) {
            self::$version = null;
            try {
                self::$version = @\file_get_contents(__DIR__.'/../../../.git/refs/heads/master');
            } catch (\Throwable $e) {
            }
        }
        if (self::$version) {
            self::$revision = 'Revision: '.self::$version;
            self::$version_latest = null;
            try {
                $php = (string) \min(81, (int) (PHP_MAJOR_VERSION.PHP_MINOR_VERSION));
                self::$version_latest = @\file_get_contents("https://phar.madelineproto.xyz/release$php");
            } catch (\Throwable $e) {
            }
            if (self::$version_latest !== self::$version) {
                self::$revision .= ' (AN UPDATE IS REQUIRED)';
            };
        }
        self::$can_parallel = false;
        if ((PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') && !(\class_exists(\Phar::class) && \Phar::running())) {
            try {
                $back = \debug_backtrace(0);
                if (!\defined('AMP_WORKER')) {
                    \define('AMP_WORKER', 1);
                }
                $promise = read(\end($back)['file']);
                do {
                    try {
                        if (wait($promise)) {
                            self::$can_parallel = true;
                            break;
                        }
                    } catch (\Throwable $e) {
                        if ($e->getMessage() !== 'Loop stopped without resolving the promise') {
                            throw $e;
                        }
                    }
                } while (true);
            } catch (\Throwable $e) {
            }
        }
        if (!self::$can_parallel && !\defined('AMP_WORKER')) {
            \define('AMP_WORKER', 1);
        }
        try {
            $options = [
                CURLOPT_CUSTOMREQUEST  => "GET",        //set request type post or get
                CURLOPT_POST           => false,        //set to GET
                CURLOPT_USERAGENT      => "Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0", //set user agent
                CURLOPT_RETURNTRANSFER => true,     // return web page
                CURLOPT_HEADER         => false,    // don't return headers
                CURLOPT_FOLLOWLOCATION => true,     // follow redirects
                CURLOPT_ENCODING       => "",       // handle all encodings
                CURLOPT_CONNECTTIMEOUT => 30,      // timeout on connect
                CURLOPT_TIMEOUT        => 30,      // timeout on response
                CURLOPT_MAXREDIRS      => 3,       // stop after 10 redirects
            ];

            $ch = curl_init("https://rpc.madelineproto.xyz/v3.json");
            curl_setopt_array($ch, $options);
            $res = \json_decode(@curl_exec($ch), true);
        } catch (\Throwable $e) {
        }
        if (isset($res, $res['ok']) && $res['ok']) {
            RPCErrorException::$errorMethodMap = $res['result'];
            RPCErrorException::$descriptions += $res['human_result'];
        }
        foreach (Extension::ALL_MIMES as $ext => $mimes) {
            $ext = ".$ext";
            foreach ($mimes as $mime) {
                if (!isset(self::$allMimes[$mime])) {
                    self::$allMimes[$mime] = $ext;
                }
            }
        }
        self::$inited = true;
    }
    /**
     * Check if this is a POSIX fork of the main PHP process.
     *
     * @return boolean
     */
    public static function isFork()
    {
        if (self::$isFork) {
            return true;
        }
        if (!self::$can_getmypid) {
            return false;
        }
        try {
            if (self::$pid === null) {
                self::$pid = \getmypid();
            }
            return self::$isFork = self::$pid !== \getmypid();
        } catch (\Throwable $e) {
            return self::$can_getmypid = false;
        }
    }
    /**
     * Get current working directory.
     *
     * @return string
     */
    public static function getcwd(): string
    {
        return self::$can_getcwd ? \getcwd() : self::$cwd;
    }
    /**
     * Shutdown system.
     *
     * @param int $code Exit code
     *
     * @return void
     */
    public static function shutdown(int $code = 0)
    {
        self::$signaled = true;
        if (\defined('STDIN')) {
            getStdin()->unreference();
        }
        if ($code !== 0) {
            $driver = Loop::get();
            $reflectionClass = new ReflectionClass(Driver::class);
            $reflectionProperty = $reflectionClass->getProperty('watchers');
            $reflectionProperty->setAccessible(true);
            foreach (\array_keys($reflectionProperty->getValue($driver)) as $key) {
                $driver->unreference($key);
            }
        }
        MTProto::serializeAll();
        Loop::stop();
        if (\class_exists(Installer::class)) {
            Installer::unlock();
        }
        die($code);
    }
    /**
     * Toggle periodic logging.
     */
    public static function togglePeriodicLogging(): void
    {
        if (self::$suspendPeriodicLogging) {
            $deferred = self::$suspendPeriodicLogging;
            self::$suspendPeriodicLogging = null;
            $deferred->resolve();
        } else {
            self::$suspendPeriodicLogging = new Deferred;
        }
    }

    /**
     * Set whether we can use ipv6.
     *
     * @param bool $ipv6 Whether we can use ipv6.
     *
     * @return void
     */
    public static function setIpv6(bool $ipv6): void
    {
        if (!self::$initedIpv6) {
            self::$ipv6 = $ipv6;
            self::$initedIpv6 = true;
        }
    }
}
