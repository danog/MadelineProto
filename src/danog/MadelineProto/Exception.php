<?php

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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

class Exception extends \Exception
{
    use TL\PrettyException;
    public static $rollbar = true;

    public function __toString()
    {
        return $this->file === 'MadelineProto' ? $this->message : '\\danog\\MadelineProto\\Exception'.($this->message !== '' ? ': ' : '').$this->message.' in '.$this->file.':'.$this->line.PHP_EOL.\danog\MadelineProto\Magic::$revision.PHP_EOL.'TL Trace (YOU ABSOLUTELY MUST READ THE TEXT BELOW):'.PHP_EOL.$this->getTLTrace();
    }

    public function __construct($message = null, $code = 0, self $previous = null, $file = null, $line = null)
    {
        $this->prettifyTL();
        if ($file !== null) {
            $this->file = $file;
        }
        if ($line !== null) {
            $this->line = $line;
        }
        parent::__construct($message, $code, $previous);
        if (\strpos($message, 'socket_accept') === false) {
            \danog\MadelineProto\Logger::log($message.' in '.\basename($this->file).':'.$this->line, \danog\MadelineProto\Logger::FATAL_ERROR);
        }

        if (\in_array($message, ['The session is corrupted!', 'Re-executing query...', 'I had to recreate the temporary authorization key', 'This peer is not present in the internal peer database', "Couldn't get response", 'Chat forbidden', 'The php-libtgvoip extension is required to accept and manage calls. See daniil.it/MadelineProto for more info.', 'File does not exist', 'Please install this fork of phpseclib: https://github.com/danog/phpseclib'])) {
            return;
        }
        if (\strpos($message, 'pg_query') !== false || \strpos($message, 'Undefined variable: ') !== false || \strpos($message, 'socket_write') !== false || \strpos($message, 'socket_read') !== false || \strpos($message, 'Received request to switch to DC ') !== false || \strpos($message, "Couldn't get response") !== false || \strpos($message, 'Re-executing query...') !== false || \strpos($message, "Couldn't find peer by provided") !== false || \strpos($message, 'id.pwrtelegram.xyz') !== false || \strpos($message, 'Please update ') !== false || \strpos($message, 'posix_isatty') !== false) {
            return;
        }
        if (self::$rollbar && \class_exists('\\Rollbar\\Rollbar')) {
            \Rollbar\Rollbar::log(\Rollbar\Payload\Level::error(), $this, \debug_backtrace(0));
        }
    }

    public static function extension(string $extensionName)
    {
        $additional = 'Try running sudo apt-get install php'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'-'.$extensionName.'.';
        if ($extensionName === 'libtgvoip') {
            $additional = 'Follow the instructions @ https://voip.madelineproto.xyz to install it.';
        } elseif ($extensionName === 'prime') {
            $additional = 'Follow the instructions @ https://prime.madelineproto.xyz to install it.';
        }
        $message = 'MadelineProto requires the '.$extensionName.' extension to run. '.$additional;
        if (PHP_SAPI !== 'cli') {
            echo $message.'<br>';
        }
        $file = 'MadelineProto';
        $line = 1;
        return new self($message, 0, null, $file, $line);
    }
    /**
     * ExceptionErrorHandler.
     *
     * Error handler
     */
    public static function exceptionErrorHandler($errno = 0, $errstr = null, $errfile = null, $errline = null)
    {
        // If error is suppressed with @, don't throw an exception
        if (\error_reporting() === 0
            || \strpos($errstr, 'headers already sent')
            || ($errfile
            && (\strpos($errfile, 'vendor/amphp') !== false || \strpos($errfile, 'vendor/league') !== false))
        ) {
            return false;
        }

        throw new self($errstr, $errno, null, $errfile, $errline);
    }

    /**
     * ExceptionErrorHandler.
     *
     * Error handler
     */
    public static function exceptionHandler($exception)
    {
        Logger::log($exception, Logger::FATAL_ERROR);
        Magic::shutdown(1);
    }
}
