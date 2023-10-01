<?php declare(strict_types=1);

/**
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

namespace danog\MadelineProto\Ipc\Runner;

use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use Phar;

/**
 * @internal
 */
abstract class RunnerAbstract
{
    private const SCRIPT_PATH = __DIR__.'/entry.php';

    /** @var array<string, string> External version of SCRIPT_PATH if inside a PHAR. */
    private static array $pharScriptPath = [];

    protected static function getScriptPath(string $alternateTmpDir = ''): string
    {
        /**
         * If using madeline.php, simply return madeline.php path.
         */
        if (\defined('MADELINE_PHP')) {
            return \MADELINE_PHP;
        }
        if (!str_starts_with(self::SCRIPT_PATH, 'phar://')) {
            return self::SCRIPT_PATH;
        }
        $alternateTmpDir = $alternateTmpDir ?: sys_get_temp_dir();

        if (isset(self::$pharScriptPath[$alternateTmpDir])) {
            return self::$pharScriptPath[$alternateTmpDir];
        }
        $path = \dirname(self::SCRIPT_PATH);

        $contents = file_get_contents(self::SCRIPT_PATH);
        $contents = str_replace('__DIR__', var_export($path, true), $contents);
        $suffix = API::RELEASE.'___'.bin2hex(random_bytes(10));
        self::$pharScriptPath[$alternateTmpDir] = $scriptPath = $alternateTmpDir.'/madeline-ipc-'.$suffix.'.php';
        file_put_contents($scriptPath, $contents, LOCK_EX);
        Logger::log("Copied IPC bootstrap file to $scriptPath");

        register_shutdown_function(static function () use ($alternateTmpDir): void {
            @unlink(self::$pharScriptPath[$alternateTmpDir]);
        });

        return $scriptPath;
    }
    /**
     * Runner.
     *
     * @param string $session Session path
     */
    abstract public static function start(string $session, int $startupId): bool;
}
