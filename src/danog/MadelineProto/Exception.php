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

class Exception extends \Exception
{
    use TL\PrettyException;
    public static $rollbar = true;

    public function __toString()
    {
        return $this->file === 'MadelineProto' ? $this->message : '\\danog\\MadelineProto\\Exception'.($this->message !== '' ? ': ' : '').$this->message.' in '.$this->file.':'.$this->line.PHP_EOL.'Revision: '.@file_get_contents(__DIR__.'/../../../.git/refs/heads/master').PHP_EOL.'TL Trace (YOU ABSOLUTELY MUST READ THE TEXT BELOW):'.PHP_EOL.$this->getTLTrace();
    }

    public function __construct($message = null, $code = 0, self $previous = null, $file = null, $line = null)
    {
        if (is_array($message) && $message[0] === 'extension') {
            if ($message[1] === 'libtgvoip') {
                $additional = 'Follow the instructions @ https://voip.madelineproto.xyz to install it.';
            } elseif ($message[1] === 'prime') {
                $additional = 'Follow the instructions @ https://prime.madelineproto.xyz to install it.';
            } else {
                $additional = 'Try running sudo apt-get install php'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'-'.$message[1].'.';
            }
            $message = 'MadelineProto requires the '.$message[1].' extension to run. '.$additional;
            if (php_sapi_name() !== 'cli') {
                echo $message.'<br>';
            }
            $file = 'MadelineProto';
            $line = 1;
        }
        $this->prettify_tl();
        if ($file !== null) {
            if (basename($file) === 'Threaded.php') {
                $line = debug_backtrace(0)[2]['line'];
                $file = debug_backtrace(0)[2]['file'];
            }
            $this->file = $file;
        }
        if ($line !== null) {
            $this->line = $line;
        }
        parent::__construct($message, $code, $previous);
        \danog\MadelineProto\Logger::log($message.' in '.basename($this->file).':'.$this->line, \danog\MadelineProto\Logger::FATAL_ERROR);
        if (in_array($message, ['The session is corrupted!', 'Re-executing query...', 'I had to recreate the temporary authorization key', 'This peer is not present in the internal peer database', "Couldn't get response", 'Chat forbidden', 'The php-libtgvoip extension is required to accept and manage calls. See daniil.it/MadelineProto for more info.', 'File does not exist', 'Please install this fork of phpseclib: https://github.com/danog/phpseclib'])) {
            return;
        }
        if (strpos($message, 'pg_query') !== false || strpos($message, 'Undefined variable: ') !== false || strpos($message, 'socket_write') !== false || strpos($message, 'socket_read') !== false || strpos($message, 'Received request to switch to DC ') !== false || strpos($message, "Couldn't get response") !== false || strpos($message, 'Re-executing query...') !== false || strpos($message, "Couldn't find peer by provided") !== false || strpos($message, 'id.pwrtelegram.xyz') !== false || strpos($message, 'Please update ') !== false || strpos($message, 'posix_isatty') !== false) {
            return;
        }
        if (self::$rollbar) {
            \Rollbar\Rollbar::log(\Rollbar\Payload\Level::error(), $this, debug_backtrace(0));
        }
    }

    /**
     * ExceptionErrorHandler.
     *
     * Error handler
     */
    public static function ExceptionErrorHandler($errno = 0, $errstr = null, $errfile = null, $errline = null)
    {
        // If error is suppressed with @, don't throw an exception
        if (error_reporting() === 0) {
            return true;
            // return true to continue through the others error handlers
        }

        throw new self($errstr, $errno, null, $errfile, $errline);
    }
}
