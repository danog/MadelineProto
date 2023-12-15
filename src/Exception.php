<?php

declare(strict_types=1);

/**
 * Exception module.
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

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;
use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;
use const PHP_SAPI;

/**
 * Basic exception.
 */
class Exception extends \Exception
{
    use TL\PrettyException;
    public function __toString(): string
    {
        return $this->file === 'MadelineProto' ? $this->message : '\\danog\\MadelineProto\\Exception'.($this->message !== '' ? ': ' : '').$this->message.' in '.$this->file.':'.$this->line.PHP_EOL.Magic::$revision.PHP_EOL.'TL Trace:'.PHP_EOL.$this->getTLTrace();
    }
    public function __construct($message = null, $code = 0, ?\Throwable $previous = null, $file = null, $line = null)
    {
        $this->prettifyTL();
        if ($file !== null) {
            $this->file = $file;
        }
        if ($line !== null) {
            $this->line = $line;
        }
        parent::__construct($message, $code, $previous);
        if (!str_contains($message, 'socket_accept')
            && $message !== 'Client backtrace'
            && !\in_array(basename($this->file), ['PKCS8.php', 'PSS.php'], true)
        ) {
            Logger::log($message.' in '.basename($this->file).':'.$this->line, Logger::FATAL_ERROR);
        }
    }
    /**
     * Complain about missing extensions.
     *
     * @param string $extensionName Extension name
     */
    public static function extension(string $extensionName): self
    {
        if ($extensionName === 'libtgvoip') {
            $additional = sprintf(Lang::$current_lang['extensionRequiredInstallWithCustomInstructions'], 'https://voip.madelineproto.xyz');
        } elseif ($extensionName === 'prime') {
            $additional = sprintf(Lang::$current_lang['extensionRequiredInstallWithCustomInstructions'], 'https://prime.madelineproto.xyz');
        } else {
            $additional = sprintf(Lang::$current_lang['extensionRequiredInstallWithApt'], 'php'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'-'.$extensionName);
        }
        $message = sprintf(Lang::$current_lang['extensionRequired'], $extensionName, $additional);
        if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
            echo htmlentities($message).'<br>';
        }
        $file = 'MadelineProto';
        $line = 1;
        return new self($message, 0, null, $file, $line);
    }
    /**
     * @internal
     *
     * Error handler
     */
    public static function exceptionErrorHandler($errno = 0, $errstr = null, $errfile = null, $errline = null): bool
    {
        $errfileReplaced = preg_replace('/phabel-transpiler\d+\./', '', $errfile ?? '');
        // If error is suppressed with @, don't throw an exception
        if (error_reporting() === 0
            || strpos($errstr, 'headers already sent')
            || str_contains($errstr, 'Creation of dynamic property')
            || str_contains($errstr, 'Legacy nullable type detected')
            || str_contains($errstr, '$tdMethods is deprecated')
            || $errfileReplaced && (
                str_contains($errfileReplaced, DIRECTORY_SEPARATOR.'amphp'.DIRECTORY_SEPARATOR)
                || str_contains($errfileReplaced, DIRECTORY_SEPARATOR.'league'.DIRECTORY_SEPARATOR)
                || str_contains($errfileReplaced, DIRECTORY_SEPARATOR.'phpseclib'.DIRECTORY_SEPARATOR)
            )
        ) {
            return false;
        }
        throw new self($errstr, $errno, null, $errfile, $errline);
    }
    /**
     * @internal
     *
     * ExceptionErrorHandler.
     */
    public static function exceptionHandler(\Throwable $exception): void
    {
        $print = static function (string $s): void {
            Logger::log($s, Logger::FATAL_ERROR);
            if (headers_sent()) {
                return;
            }
            http_response_code(500);
            if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
                echo($s.PHP_EOL);
            } else {
                echo(str_replace("\n", "<br>", htmlentities($s)).PHP_EOL);
            }
        };
        if (str_contains($exception->getMessage(), 'Fiber stack protect failed')
            || str_contains($exception->getMessage(), 'Fiber stack allocate failed')
        ) {
            $maps = "?";
            try {
                $maps = '~'.substr_count(file_get_contents('/proc/self/maps'), "\n");
                $pid = getmypid();
                $maps = '~'.substr_count(file_get_contents("/proc/$pid/maps"), "\n");
            } catch (\Throwable) {
            }
            $print(Lang::$current_lang['manualAdminActionRequired']);
            $print(Lang::$current_lang['manualAdminActionRequired']);
            $print(Lang::$current_lang['manualAdminActionRequired']);
            $print(sprintf(Lang::$current_lang['mmapErrorPart1'], $maps));
            $print(sprintf(Lang::$current_lang['mmapErrorPart2'], 'echo 262144 | sudo tee /proc/sys/vm/max_map_count'));
            $print(sprintf(Lang::$current_lang['mmapErrorPart3'], 'echo vm.max_map_count=262144 | sudo tee /etc/sysctl.d/40-madelineproto.conf'));
            $print(Lang::$current_lang['mmapErrorPart4']);
            $print(Lang::$current_lang['manualAdminActionRequired']);
            $print(Lang::$current_lang['manualAdminActionRequired']);
            $print(Lang::$current_lang['manualAdminActionRequired']);
        }
        $print((string) $exception);
        die(1);
    }
}
