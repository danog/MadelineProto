<?php

declare(strict_types=1);

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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use const DIRECTORY_SEPARATOR;
use const HAD_MADELINE_PHAR;

/**
 * Snitch.
 */
final class Snitch
{
    /**
     * Maximum starts without a phar file.
     */
    const MAX_NO_PHAR_STARTS = 10;

    /**
     * Whether madeline.phar was downloaded from scratch.
     */
    private array $hadInstalled = [];

    /**
     * Called before serialization.
     */
    public function __sleep(): array
    {
        return ['hadInstalled'];
    }
    /**
     * Wakeup function.
     */
    public function __wakeup(): void
    {
        if (\defined('HAD_MADELINE_PHAR')) {
            $this->hadInstalled []= HAD_MADELINE_PHAR;
            if (\count($this->hadInstalled) > self::MAX_NO_PHAR_STARTS) {
                \array_shift($this->hadInstalled);
                if (!\array_sum($this->hadInstalled)) { // For three times, MadelineProto was started with no phar file
                    //$this->die();
                }
            }
        }
    }

    /**
     * Die.
     */
    private function die(): void
    {
        Shutdown::removeCallback('restarter');
        $message = 'Please do not remove madeline.phar or madeline.php, or else MadelineProto will crash. If you have any problem with MadelineProto, report it to https://github.com/danog/MadelineProto or https://t.me/pwrtelegramgroup';
        Logger::log($message, Logger::FATAL_ERROR);
        \file_put_contents(Magic::$cwd.DIRECTORY_SEPARATOR.'DO_NOT_REMOVE_MADELINEPROTO_LOG_SESSION', $message);
        die("$message\n");
    }
}
