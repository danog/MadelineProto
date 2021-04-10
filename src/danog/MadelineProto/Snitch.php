<?php
/**
 * Snitch module.
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

/**
 * Snitch.
 */
class Snitch
{
    /**
     * Maximum starts without a phar file.
     */
    const MAX_NO_PHAR_STARTS = 3;
    /**
     * Maximum starts without a logfile.
     */
    const MAX_NO_LOG_STARTS = 3;

    /**
     * Whether madeline.phar was downloaded from scratch.
     */
    private array $hadInstalled = [];
    /**
     * Whether logs were enabled.
     */
    private array $hadLog = [];

    /**
     * Whether we currently have a logfile.
     */
    private static bool $hasLog = true;
    /**
     * Whether we checked the logfile.
     */
    private static int $snitchedLog = 0;

    /**
     * Snitch on logfile.
     *
     * @param string $file Logfile
     *
     * @return void
     */
    public static function logFile(string $file)
    {
        if (self::$snitchedLog) {
            return;
        }
        self::$snitchedLog = 1;
        \clearstatcache(true, $file);
        self::$hasLog = \file_exists($file);
    }
    /**
     * Called before serialization.
     *
     * @return array
     */
    public function __sleep()
    {
        if (self::$snitchedLog === 1) {
            $this->hadLog []= self::$hasLog;
            if (\count($this->hadLog) > self::MAX_NO_LOG_STARTS) {
                \array_shift($this->hadLog);
                if (!\array_sum($this->hadLog)) { // For three times, MadelineProto was started with no logfile
                    $this->die();
                }
            }
            self::$snitchedLog++;
        }
        return ['hadInstalled', 'hadLog'];
    }
    /**
     * Wakeup function.
     */
    public function __wakeup()
    {
        if (\defined('HAD_MADELINE_PHAR')) {
            $this->hadInstalled []= \HAD_MADELINE_PHAR;
            if (\count($this->hadInstalled) > self::MAX_NO_PHAR_STARTS) {
                \array_shift($this->hadInstalled);
                if (!\array_sum($this->hadInstalled)) { // For three times, MadelineProto was started with no phar file
                    $this->die();
                }
            }
        }
    }

    /**
     * Die.
     *
     * @return void
     */
    private function die(): void
    {
        Shutdown::removeCallback('restarter');
        $message = "Please do not remove madeline.phar, madeline.php and MadelineProto.log, or else MadelineProto will crash. If you have any problem with MadelineProto, report it to https://github.com/danog/MadelineProto or https://t.me/pwrtelegramgroup";
        Logger::log($message, Logger::FATAL_ERROR);
        \file_put_contents(Magic::$cwd.DIRECTORY_SEPARATOR.'DO_NOT_REMOVE_MADELINEPROTO_LOG_SESSION', $message);
        die("$message\n");
    }
}
