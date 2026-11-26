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

    public function testWebEntryRequiresTheStartupTokenOfTheRunner(): void
    {
        $sessionDir = sys_get_temp_dir().'/madeline-entry-sec-'.bin2hex(random_bytes(8));
        mkdir($sessionDir, 0o700, true);
        $tokenFile = $sessionDir.DIRECTORY_SEPARATOR.WebRunner::TOKEN_FILE;
        $entryPath = realpath(\dirname(__DIR__, 3).'/src/Ipc/Runner/entry.php');
        $autoload = realpath(\dirname(__DIR__, 3).'/vendor/autoload.php');
        self::assertIsString($entryPath);
        self::assertIsString($autoload);

        $prependFile = $sessionDir.DIRECTORY_SEPARATOR.'prepend.php';
        /**
         * Run entry.php as a request would: no command line, the arguments in $_GET (or, with $phar, already
         * defined by madeline.php). An accepted worker gets as far as opening the session, which here exits with 42.
         *
         * @return array{0: int, 1: string} The exit code, and what the worker reported
         */
        $runEntry = static function (array $get, bool $phar = false) use ($entryPath, $autoload, $prependFile): array {
            $define = $phar
                ? \sprintf('define("MADELINE_WORKER_TYPE", %s); define("MADELINE_WORKER_ARGS", %s);', var_export(array_shift($get['argv']), true), var_export($get['argv'], true))
                : '';
            file_put_contents($prependFile, \sprintf(
                '<?php namespace danog\MadelineProto { require %s; final class SessionPaths { public function __construct() { fwrite(STDERR, "ACCEPTED ".getcwd()); exit(42); } } }
                namespace { unset($argv, $GLOBALS["argv"]); $_GET = %s; %s
                register_shutdown_function(static function () { if (http_response_code() === 403) { fwrite(STDERR, "FORBIDDEN"); } }); }',
                var_export($autoload, true),
                var_export($get, true),
                $define,
            ));
            $cmd = escapeshellarg(PHP_BINARY).' -d '.escapeshellarg("auto_prepend_file=$prependFile").' '.escapeshellarg($entryPath);
            $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
            $proc = proc_open($cmd, $descriptors, $pipes, sys_get_temp_dir());
            self::assertIsResource($proc);
            fclose($pipes[0]);
            stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            return [proc_close($proc), $stderr];
        };
        $writeToken = static function (string $token, int $startupId, int $time) use ($tokenFile, $sessionDir): void {
            file_put_contents($tokenFile, WebRunner::TOKEN_HEADER.json_encode([
                'token' => $token,
                'cwd' => base64_encode($sessionDir),
                'startupId' => $startupId,
                'time' => $time,
            ], JSON_THROW_ON_ERROR));
        };
        $forbidden = [1, 'FORBIDDEN'];

        try {
            foreach ([false, true] as $phar) {
                // Missing token
                self::assertSame($forbidden, $runEntry(['argv' => ['madeline-ipc', $sessionDir, '123']], $phar));

                // Wrong token: the file stays
                $writeToken(str_repeat('a', 64), 123, time());
                self::assertSame($forbidden, $runEntry(['argv' => ['madeline-ipc', $sessionDir, '123'], 'token' => str_repeat('b', 64)], $phar));
                self::assertFileExists($tokenFile);

                // Token of another startup
                self::assertSame($forbidden, $runEntry(['argv' => ['madeline-ipc', $sessionDir, '999'], 'token' => str_repeat('a', 64)], $phar));

                // Expired token
                $writeToken(str_repeat('c', 64), 123, time() - WebRunner::TOKEN_TTL - 10);
                self::assertSame($forbidden, $runEntry(['argv' => ['madeline-ipc', $sessionDir, '123'], 'token' => str_repeat('c', 64)], $phar));

                // The token of the runner: used up, and the worker runs in the directory it stored, not the request's
                $writeToken(str_repeat('d', 64), 123, time());
                self::assertSame([42, 'ACCEPTED '.realpath($sessionDir)], $runEntry(['argv' => ['madeline-ipc', $sessionDir, '123'], 'token' => str_repeat('d', 64)], $phar));
                self::assertFileDoesNotExist($tokenFile);
                self::assertSame($forbidden, $runEntry(['argv' => ['madeline-ipc', $sessionDir, '123'], 'token' => str_repeat('d', 64)], $phar));
            }

            // A stream wrapper as the session path
            [$code] = $runEntry(['argv' => ['madeline-ipc', 'phar:///tmp/evil.phar', '123'], 'token' => str_repeat('c', 64)]);
            self::assertNotSame(0, $code);
            self::assertNotSame(42, $code);
        } finally {
            foreach (glob($sessionDir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($sessionDir);
        }
    }

    public function testTheRunnersOfAStartupShareItsToken(): void
    {
        $sessionDir = sys_get_temp_dir().'/madeline-token-'.bin2hex(random_bytes(8));
        mkdir($sessionDir, 0o700, true);
        try {
            $token = WebRunner::createStartupToken($sessionDir, 1);
            self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
            self::assertSame($token, WebRunner::createStartupToken($sessionDir, 1));
            $other = WebRunner::createStartupToken($sessionDir, 2);
            self::assertNotSame($token, $other);
            // Written whole, under its final name only
            self::assertSame([WebRunner::TOKEN_FILE], array_values(array_diff(scandir($sessionDir), ['.', '..'])));
            $raw = file_get_contents($sessionDir.DIRECTORY_SEPARATOR.WebRunner::TOKEN_FILE);
            self::assertStringStartsWith(WebRunner::TOKEN_HEADER, $raw);
            $data = json_decode(substr($raw, \strlen(WebRunner::TOKEN_HEADER)), true, flags: JSON_THROW_ON_ERROR);
            self::assertSame($other, $data['token']);
            self::assertSame(2, $data['startupId']);
            self::assertSame(getcwd(), base64_decode($data['cwd'], true));
        } finally {
            foreach (glob($sessionDir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($sessionDir);
        }
    }
}
