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

use Amp\Process\Process;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function Amp\ByteStream\buffer;

/**
 * Every file of src compiles without a diagnostic on the running PHP: MadelineProto's error handler throws for
 * deprecations too, so one emitted when a file is compiled (PHP 8.6: returning from a finally block, a value from a
 * constructor...) makes loading its class throw, wherever it's first used. Linted in a process of its own, with all
 * errors reported: the light tests run on every supported PHP, the CI image on the newest.
 *
 * @internal
 */
final class CompileDiagnosticsTest extends TestCase
{
    public function testSrcCompilesWithoutDiagnostics(): void
    {
        $src = \dirname(__DIR__, 3).'/src/';
        $files = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src)) as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        sort($files);
        $diagnostics = [];
        // php -l lints several files at once (PHP 8.3+), in batches to keep the command line short
        foreach (array_chunk($files, 200) as $batch) {
            $process = Process::start([
                PHP_BINARY, '-n', '-d', 'error_reporting=-1', '-d', 'display_errors=stderr', '-d', 'log_errors=0',
                '-l', ...$batch,
            ]);
            $stdout = buffer($process->getStdout());
            $stderr = buffer($process->getStderr());
            self::assertSame(0, $process->join(), $stdout.$stderr);
            foreach (explode("\n", $stderr) as $line) {
                if (trim($line) !== '') {
                    $diagnostics[] = str_replace($src, '', $line);
                }
            }
        }
        self::assertSame([], $diagnostics, 'src emits diagnostics when compiled');
    }
}
