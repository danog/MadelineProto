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

use AssertionError;
use danog\MadelineProto\Ipc\Runner\WebRunner;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Tools;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

use const PHP_BINARY;

/** @internal */
final class SecurityHardeningTest extends TestCase
{
    public function testValidateServerNameAcceptsValidHostsAndRejectsInjections(): void
    {
        foreach (['example.com', 'sub.example.com:8443', 'localhost', '127.0.0.1', '127.0.0.1:8080', '[::1]', '[::1]:443', '::1'] as $valid) {
            self::assertSame($valid, Tools::validateServerName($valid));
        }

        foreach ([
            '',
            "example.com\r\nX-Injected: 1",
            "example.com\nfoo",
            "example.com\0",
            'example.com/path',
            'example.com?query=1',
            'example.com#frag',
            'user@example.com',
            'https://example.com',
            'example.com:99999',
            'example.com:0',
            'example.com:abc',
            'bad host',
            '[::1]:99999',
            '[not-ipv6]',
        ] as $invalid) {
            try {
                Tools::validateServerName($invalid);
                self::fail("Expected AssertionError for invalid server name: $invalid");
            } catch (AssertionError) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testWebRunnerSelfStartRejectsCrlfInUriOrServerName(): void
    {
        $prevServerName = $_SERVER['SERVER_NAME'] ?? null;
        try {
            $_SERVER['SERVER_NAME'] = 'example.com';
            $this->expectException(AssertionError::class);
            WebRunner::selfStart("/entry.php\r\nX-Injected: 1");
        } finally {
            if ($prevServerName === null) {
                unset($_SERVER['SERVER_NAME']);
            } else {
                $_SERVER['SERVER_NAME'] = $prevServerName;
            }
        }
    }

    public function testAppInfoRejectsInvalidAcceptLanguage(): void
    {
        $prevAccept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
        $prevLang = $_SERVER['LANG'] ?? null;
        try {
            unset($_SERVER['LANG']);
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] = '../../etc/passwd<script>alert(1)</script>';
            self::assertSame('en', (new AppInfo)->getLangCode());

            $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ckb-IQ,en;q=0.8';
            self::assertSame('ckb', (new AppInfo)->getLangCode());

            $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'it-IT,it;q=0.9';
            self::assertSame('it', (new AppInfo)->getLangCode());

            $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'POSIX';
            self::assertSame('en', (new AppInfo)->getLangCode());
        } finally {
            if ($prevAccept === null) {
                unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            } else {
                $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $prevAccept;
            }
            if ($prevLang === null) {
                unset($_SERVER['LANG']);
            } else {
                $_SERVER['LANG'] = $prevLang;
            }
        }
    }

    public function testFilesParseMimeTypeValidatesContentTypeHeader(): void
    {
        $parse = new ReflectionMethod(MTProto::class, 'parseMimeType');
        self::assertSame('image/png', $parse->invoke(null, 'image/png'));
        self::assertSame('video/mp4', $parse->invoke(null, 'Video/MP4; codecs="avc1"'));
        self::assertNull($parse->invoke(null, null));
        self::assertNull($parse->invoke(null, ''));
        self::assertNull($parse->invoke(null, "text/html\r\nX-Injected: 1"));
        self::assertNull($parse->invoke(null, '../etc/passwd'));
        self::assertNull($parse->invoke(null, 'not-a-mime'));
        self::assertNull($parse->invoke(null, 'image/'.str_repeat('a', 300)));
    }

    public function testWebEntryRejectsUnauthenticatedOrInvalidTokenRequests(): void
    {
        $sessionDir = sys_get_temp_dir().'/madeline-entry-sec-'.bin2hex(random_bytes(8));
        mkdir($sessionDir, 0o700, true);
        $entryPath = realpath(\dirname(__DIR__, 3).'/src/Ipc/Runner/entry.php');
        self::assertIsString($entryPath);

        $prependFile = $sessionDir.DIRECTORY_SEPARATOR.'prepend.php';
        $runEntry = static function (array $get) use ($entryPath, $prependFile): int {
            file_put_contents(
                $prependFile,
                \sprintf('<?php unset($argv, $GLOBALS["argv"]); $_GET = %s;', var_export($get, true)),
            );
            $cmd = escapeshellarg(PHP_BINARY).' -d '.escapeshellarg("auto_prepend_file=$prependFile").' '.escapeshellarg($entryPath);
            $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
            $proc = proc_open($cmd, $descriptors, $pipes);
            self::assertIsResource($proc);
            fclose($pipes[0]);
            stream_get_contents($pipes[1]);
            stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            return proc_close($proc);
        };

        try {
            // 1. Missing token
            self::assertNotSame(0, $runEntry([
                'argv' => ['madeline-ipc', $sessionDir, '123'],
                'cwd' => '/tmp',
            ]));

            // 2. Wrong token
            file_put_contents(
                $sessionDir.DIRECTORY_SEPARATOR.WebRunner::TOKEN_FILE,
                WebRunner::TOKEN_HEADER.json_encode([
                    'token' => str_repeat('a', 64),
                    'cwd' => $sessionDir,
                    'startupId' => 123,
                    'time' => time(),
                ], JSON_THROW_ON_ERROR),
            );
            self::assertNotSame(0, $runEntry([
                'argv' => ['madeline-ipc', $sessionDir, '123'],
                'token' => str_repeat('b', 64),
            ]));
            // Token file must not be deleted on failed auth
            self::assertFileExists($sessionDir.DIRECTORY_SEPARATOR.WebRunner::TOKEN_FILE);

            // 3. Mismatched startupId
            self::assertNotSame(0, $runEntry([
                'argv' => ['madeline-ipc', $sessionDir, '999'],
                'token' => str_repeat('a', 64),
            ]));

            // 4. Expired token
            file_put_contents(
                $sessionDir.DIRECTORY_SEPARATOR.WebRunner::TOKEN_FILE,
                WebRunner::TOKEN_HEADER.json_encode([
                    'token' => str_repeat('c', 64),
                    'cwd' => $sessionDir,
                    'startupId' => 123,
                    'time' => time() - WebRunner::TOKEN_TTL - 10,
                ], JSON_THROW_ON_ERROR),
            );
            self::assertNotSame(0, $runEntry([
                'argv' => ['madeline-ipc', $sessionDir, '123'],
                'token' => str_repeat('c', 64),
            ]));

            // 5. Stream wrapper in session path
            self::assertNotSame(0, $runEntry([
                'argv' => ['madeline-ipc', 'phar:///tmp/evil.phar', '123'],
                'token' => str_repeat('c', 64),
            ]));
        } finally {
            @unlink($prependFile);
            @unlink($sessionDir.DIRECTORY_SEPARATOR.WebRunner::TOKEN_FILE);
            @rmdir($sessionDir);
        }
    }
}
