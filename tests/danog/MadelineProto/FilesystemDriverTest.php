<?php

declare(strict_types=1);

/**
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

namespace danog\MadelineProto\Test;

use Amp\File\Driver\ParallelFilesystemDriver;
use danog\MadelineProto\Magic;
use PHPUnit\Framework\TestCase;

use function Amp\delay;
use function Amp\File\filesystem;
use function Amp\File\openFile;

/**
 * Magic::start() replaces the filesystem driver of amphp/file: files opened before keep working.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class FilesystemDriverTest extends TestCase
{
    public function testFilesOpenedBeforeStartAreClosedAfterIt(): void
    {
        // amphp/file's default driver without ext-uv or ext-eio: a pool of workers holding the files.
        filesystem(new ParallelFilesystemDriver);
        $path = tempnam(sys_get_temp_dir(), 'madeline-filesystem');
        try {
            $file = openFile($path, 'r');
            // Destroying the file queues its close on its worker.
            unset($file);
            // Replacing the driver must not kill that worker (the close threw in the event loop).
            Magic::start(light: true);
            delay(0.1);
            self::assertFileExists($path);
        } finally {
            unlink($path);
        }
    }
}
