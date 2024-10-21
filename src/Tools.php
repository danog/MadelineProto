<?php

declare(strict_types=1);

/**
 * Tools module.
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

use Amp\ByteStream\Pipe;
use Amp\ByteStream\ReadableBuffer;
use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\WritableBuffer;
use Amp\Cancellation;
use Amp\File\File;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Process\Process;
use ArrayAccess;
use Closure;
use Countable;
use danog\DialogId\DialogId;
use Fiber;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\DeclareDeclare;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\ParserFactory;
use phpseclib3\Crypt\Random;
use ReflectionClass;
use Traversable;

use Webmozart\Assert\Assert;

use const DIRECTORY_SEPARATOR;
use const PHP_INT_MAX;
use const PHP_SAPI;

use const STR_PAD_RIGHT;
use function Amp\File\openFile;
use function unpack;

/**
 * Some tools.
 */
abstract class Tools extends AsyncTools
{
    /**
     * Test fibers.
     *
     * @return array{maxFibers: int, realMemoryMb: int, maps: ?int, maxMaps: ?int}
     */
    public static function testFibers(int $fiberCount = 100000): array
    {
        ini_set('memory_limit', -1);

        $f = [];
        for ($x = 0; $x < $fiberCount; $x++) {
            try {
                $f []= $cur = new Fiber(static function (): void {
                    Fiber::suspend();
                });
                $cur->start();
            } catch (\Throwable $e) {
                break;
            }
        }
        return [
            'maxFibers' => $x,
            'realMemoryMb' => (int) (memory_get_usage(true)/1024/1024),
            'maps' => self::getMaps(),
            'maxMaps' => self::getMaxMaps(),
        ];
    }
    /**
     * Get current number of memory-mapped regions, UNIX only.
     */
    public static function getMaps(): ?int
    {
        try {
            if (file_exists('/proc/self/maps')) {
                return substr_count(@file_get_contents('/proc/self/maps'), "\n")-1;
            }
            $pid = getmypid();
            if (file_exists("/proc/$pid/maps")) {
                return substr_count(@file_get_contents("/proc/$pid/maps"), "\n")-1;
            }
        } catch (\Throwable) {
        }
        return null;
    }
    /**
     * Get maximum number of memory-mapped regions, UNIX only.
     * Use testFibers to get the maximum number of fibers on any platform.
     */
    public static function getMaxMaps(): ?int
    {
        try {
            if (file_exists('/proc/sys/vm/max_map_count')) {
                return ((int) @file_get_contents('/proc/sys/vm/max_map_count')) ?: null;
            }
        } catch (\Throwable) {
        }
        return null;
    }
    /**
     * Converts a string into an async amphp stream.
     */
    public static function stringToStream(string $str): ReadableBuffer
    {
        return new ReadableBuffer($str);
    }
    /**
     * Sanify TL obtained from JSON for TL serialization.
     *
     * @param array $input Data to sanitize
     * @internal
     */
    public static function convertJsonTL(array $input): array
    {
        $cb = static function (&$val) use (&$cb): void {
            if (isset($val['@type'])) {
                $val['_'] = $val['@type'];
            } elseif (\is_array($val)) {
                array_walk($val, $cb);
            }
        };
        array_walk($input, $cb);
        return $input;
    }
    private static function uRShift(int $a, int $b): int
    {
        if($b == 0) {
            return $a;
        }
        return ($a >> $b) & ~(1<<(8*PHP_INT_SIZE-1)>>($b-1));
    }
    /**
     * Generate MTProto vector hash.
     *
     * Returns a vector hash.
     *
     * @param array<string|int> $longs IDs
     */
    public static function genVectorHash(array $longs): string
    {
        $hash = 0;
        foreach ($longs as $long) {
            if (\is_string($long)) {
                $long = self::unpackSignedLong(strrev(substr(md5($long, true), 0, 8)));
            }
            $hash ^= self::uRShift($hash, 21);
            $hash ^= $hash << 35;
            $hash ^= self::uRShift($hash, 4);
            $hash = $hash + $long;
        }
        return self::packSignedLong($hash);
    }
    /**
     * Get random integer.
     *
     * @param integer $modulus Modulus
     */
    public static function randomInt(int $modulus = 0): int
    {
        if ($modulus === 0) {
            return random_int(PHP_INT_MIN, PHP_INT_MAX);
        }
        return random_int(0, PHP_INT_MAX) % $modulus;
    }
    /**
     * Get secure random string of specified length.
     *
     * @param integer $length Length
     */
    public static function random(int $length): string
    {
        return $length === 0 ? '' : Random::string($length);
    }
    /**
     * Positive modulo
     * Works just like the % (modulus) operator, only returns always a postive number.
     *
     * @param int $a A
     * @param int $b B
     */
    public static function posmod(int $a, int $b): int
    {
        $resto = $a % $b;
        return $resto < 0 ? $resto + abs($b) : $resto;
    }
    /**
     * Unpack base256 signed int.
     *
     * @param string $value base256 int
     */
    public static function unpackSignedInt(string $value): int
    {
        if (\strlen($value) !== 4) {
            throw new TL\Exception("Length is not 4");
        }
        return unpack('l', Magic::$BIG_ENDIAN ? strrev($value) : $value)[1];
    }
    /**
     * Unpack base256 signed long.
     *
     * @param string $value base256 long
     */
    public static function unpackSignedLong(string $value): int
    {
        if (\strlen($value) !== 8) {
            throw new TL\Exception("Length is not 8");
        }
        return unpack('q', Magic::$BIG_ENDIAN ? strrev($value) : $value)[1];
    }
    /**
     * Unpack base256 signed long to string.
     *
     * @param string|int|array $value base256 long
     */
    public static function unpackSignedLongString(string|int|array $value): string
    {
        if (\is_int($value)) {
            return (string) $value;
        }
        if (\is_array($value) && \count($value) === 2) {
            $value = pack('l2', $value);
        }
        if (\strlen($value) !== 8) {
            throw new TL\Exception("Length is not 8");
        }
        return (string) self::unpackSignedLong($value);
    }
    /**
     * Convert integer to base256 signed int.
     *
     * @param integer $value Value to convert
     */
    public static function packSignedInt(int $value): string
    {
        if ($value > 2147483647) {
            throw new TL\Exception(sprintf(Lang::$current_lang['value_bigger_than_2147483647'], $value));
        }
        if ($value < -2147483648) {
            throw new TL\Exception(sprintf(Lang::$current_lang['value_smaller_than_2147483648'], $value));
        }
        $res = pack('l', $value);
        return Magic::$BIG_ENDIAN ? strrev($res) : $res;
    }
    /**
     * Convert integer to base256 long.
     *
     * @param int $value Value to convert
     */
    public static function packSignedLong(int $value): string
    {
        return Magic::$BIG_ENDIAN ? strrev(pack('q', $value)) : pack('q', $value);
    }
    /**
     * Convert value to unsigned base256 int.
     *
     * @param int $value Value
     */
    public static function packUnsignedInt(int $value): string
    {
        if ($value > 4294967295) {
            throw new TL\Exception(sprintf(Lang::$current_lang['value_bigger_than_4294967296'], $value));
        }
        if ($value < 0) {
            throw new TL\Exception(sprintf(Lang::$current_lang['value_smaller_than_0'], $value));
        }
        return pack('V', $value);
    }
    /**
     * Convert double to binary version.
     *
     * @param float $value Value to convert
     */
    public static function packDouble(float $value): string
    {
        $res = pack('d', $value);
        if (\strlen($res) !== 8) {
            throw new TL\Exception(Lang::$current_lang['encode_double_error']);
        }
        return Magic::$BIG_ENDIAN ? strrev($res) : $res;
    }
    /**
     * Unpack binary double.
     *
     * @param string $value Value to unpack
     */
    public static function unpackDouble(string $value): float
    {
        if (\strlen($value) !== 8) {
            throw new TL\Exception("Length is not 8");
        }
        return unpack('d', Magic::$BIG_ENDIAN ? strrev($value) : $value)[1];
    }
    /**
     * Check if is array or similar (traversable && countable && arrayAccess).
     *
     * @param mixed $var Value to check
     */
    public static function isArrayOrAlike(mixed $var): bool
    {
        return \is_array($var) || $var instanceof ArrayAccess && $var instanceof Traversable && $var instanceof Countable;
    }
    /**
     * Create array.
     *
     * @param mixed ...$params Params
     */
    public static function arr(mixed ...$params): array
    {
        return $params;
    }
    /**
     * base64URL decode.
     *
     * @param string $data Data to decode
     */
    public static function base64urlDecode(string $data): string
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), \strlen($data) % 4, '=', STR_PAD_RIGHT), true);
    }
    /**
     * Base64URL encode.
     *
     * @param string $data Data to encode
     */
    public static function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    /**
     * null-byte RLE decode.
     *
     * @param string $string Data to decode
     */
    public static function rleDecode(string $string): string
    {
        $new = '';
        $last = '';
        $null = \chr(0);
        foreach (str_split($string) as $cur) {
            if ($last === $null) {
                $new .= str_repeat($last, \ord($cur));
                $last = '';
            } else {
                $new .= $last;
                $last = $cur;
            }
        }
        $string = $new.$last;
        return $string;
    }
    /**
     * null-byte RLE encode.
     *
     * @param string $string Data to encode
     */
    public static function rleEncode(string $string): string
    {
        $new = '';
        $count = 0;
        $null = \chr(0);
        foreach (str_split($string) as $cur) {
            if ($cur === $null) {
                $count++;
            } else {
                if ($count > 0) {
                    $new .= $null.\chr($count);
                    $count = 0;
                }
                $new .= $cur;
            }
        }
        return $new;
    }
    private const INFLATE_HEADER = "\xff\xd8\xff\xe0\x00\x10\x4a\x46\x49".
        "\x46\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xff\xdb\x00\x43\x00\x28\x1c".
        "\x1e\x23\x1e\x19\x28\x23\x21\x23\x2d\x2b\x28\x30\x3c\x64\x41\x3c\x37\x37".
        "\x3c\x7b\x58\x5d\x49\x64\x91\x80\x99\x96\x8f\x80\x8c\x8a\xa0\xb4\xe6\xc3".
        "\xa0\xaa\xda\xad\x8a\x8c\xc8\xff\xcb\xda\xee\xf5\xff\xff\xff\x9b\xc1\xff".
        "\xff\xff\xfa\xff\xe6\xfd\xff\xf8\xff\xdb\x00\x43\x01\x2b\x2d\x2d\x3c\x35".
        "\x3c\x76\x41\x41\x76\xf8\xa5\x8c\xa5\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8".
        "\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8".
        "\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8\xf8".
        "\xf8\xf8\xf8\xf8\xf8\xff\xc0\x00\x11\x08\x00\x00\x00\x00\x03\x01\x22\x00".
        "\x02\x11\x01\x03\x11\x01\xff\xc4\x00\x1f\x00\x00\x01\x05\x01\x01\x01\x01".
        "\x01\x01\x00\x00\x00\x00\x00\x00\x00\x00\x01\x02\x03\x04\x05\x06\x07\x08".
        "\x09\x0a\x0b\xff\xc4\x00\xb5\x10\x00\x02\x01\x03\x03\x02\x04\x03\x05\x05".
        "\x04\x04\x00\x00\x01\x7d\x01\x02\x03\x00\x04\x11\x05\x12\x21\x31\x41\x06".
        "\x13\x51\x61\x07\x22\x71\x14\x32\x81\x91\xa1\x08\x23\x42\xb1\xc1\x15\x52".
        "\xd1\xf0\x24\x33\x62\x72\x82\x09\x0a\x16\x17\x18\x19\x1a\x25\x26\x27\x28".
        "\x29\x2a\x34\x35\x36\x37\x38\x39\x3a\x43\x44\x45\x46\x47\x48\x49\x4a\x53".
        "\x54\x55\x56\x57\x58\x59\x5a\x63\x64\x65\x66\x67\x68\x69\x6a\x73\x74\x75".
        "\x76\x77\x78\x79\x7a\x83\x84\x85\x86\x87\x88\x89\x8a\x92\x93\x94\x95\x96".
        "\x97\x98\x99\x9a\xa2\xa3\xa4\xa5\xa6\xa7\xa8\xa9\xaa\xb2\xb3\xb4\xb5\xb6".
        "\xb7\xb8\xb9\xba\xc2\xc3\xc4\xc5\xc6\xc7\xc8\xc9\xca\xd2\xd3\xd4\xd5\xd6".
        "\xd7\xd8\xd9\xda\xe1\xe2\xe3\xe4\xe5\xe6\xe7\xe8\xe9\xea\xf1\xf2\xf3\xf4".
        "\xf5\xf6\xf7\xf8\xf9\xfa\xff\xc4\x00\x1f\x01\x00\x03\x01\x01\x01\x01\x01".
        "\x01\x01\x01\x01\x00\x00\x00\x00\x00\x00\x01\x02\x03\x04\x05\x06\x07\x08".
        "\x09\x0a\x0b\xff\xc4\x00\xb5\x11\x00\x02\x01\x02\x04\x04\x03\x04\x07\x05".
        "\x04\x04\x00\x01\x02\x77\x00\x01\x02\x03\x11\x04\x05\x21\x31\x06\x12\x41".
        "\x51\x07\x61\x71\x13\x22\x32\x81\x08\x14\x42\x91\xa1\xb1\xc1\x09\x23\x33".
        "\x52\xf0\x15\x62\x72\xd1\x0a\x16\x24\x34\xe1\x25\xf1\x17\x18\x19\x1a\x26".
        "\x27\x28\x29\x2a\x35\x36\x37\x38\x39\x3a\x43\x44\x45\x46\x47\x48\x49\x4a".
        "\x53\x54\x55\x56\x57\x58\x59\x5a\x63\x64\x65\x66\x67\x68\x69\x6a\x73\x74".
        "\x75\x76\x77\x78\x79\x7a\x82\x83\x84\x85\x86\x87\x88\x89\x8a\x92\x93\x94".
        "\x95\x96\x97\x98\x99\x9a\xa2\xa3\xa4\xa5\xa6\xa7\xa8\xa9\xaa\xb2\xb3\xb4".
        "\xb5\xb6\xb7\xb8\xb9\xba\xc2\xc3\xc4\xc5\xc6\xc7\xc8\xc9\xca\xd2\xd3\xd4".
        "\xd5\xd6\xd7\xd8\xd9\xda\xe2\xe3\xe4\xe5\xe6\xe7\xe8\xe9\xea\xf2\xf3\xf4".
        "\xf5\xf6\xf7\xf8\xf9\xfa\xff\xda\x00\x0c\x03\x01\x00\x02\x11\x03\x11\x00".
        "\x3f\x00";
    private const INFLATE_FOOTER = "\xff\xd9";
    /**
     * Inflate stripped photosize to full JPG payload.
     *
     * @param string $stripped Stripped photosize
     */
    public static function inflateStripped(string $stripped): string
    {
        if (\strlen($stripped) < 3 || \ord($stripped[0]) !== 1) {
            return $stripped;
        }
        $header = self::INFLATE_HEADER;
        $header[164] = $stripped[1];
        $header[166] = $stripped[2];
        return $header.substr($stripped, 3).self::INFLATE_FOOTER;
    }
    /**
     * Close connection with client, connected via web.
     *
     * @param string $message Message
     */
    public static function closeConnection(string $message): void
    {
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg' || isset($GLOBALS['exited']) || headers_sent() || isset($_GET['MadelineSelfRestart']) || Magic::$isIpcWorker) {
            return;
        }
        $buffer = @ob_get_clean() ?: '';
        $buffer .= $message;
        ignore_user_abort(true);
        header('Connection: close');
        header('Content-Type: text/html');
        echo $buffer;
        flush();
        $GLOBALS['exited'] = true;
        if (\function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }
    /**
     * Get maximum photo size.
     *
     * @param array<array{w: int, h: int, type: string, ...}> $sizes
     *
     * @internal
     */
    public static function maxSize(array $sizes): array
    {
        $maxPixels = 0;
        $max = null;
        foreach ($sizes as $size) {
            if (isset($size['w'], $size['h'])) {
                $curPixels = $size['w'] * $size['h'];
                if ($curPixels > $maxPixels) {
                    $maxPixels = $curPixels;
                    $max = $size;
                }
            }
        }
        if (!$max) {
            $maxType = 0;
            foreach ($sizes as $size) {
                $curType = \ord($size['type']);
                if ($curType > $maxType) {
                    $maxType = $curType;
                    $max = $size;
                }
            }
        }
        \assert($max !== null);
        return $max;
    }
    /**
     * Get final element of array.
     *
     * @template T
     * @param  array<T> $what Array
     * @return T
     */
    public static function end(array $what): mixed
    {
        return end($what);
    }
    /**
     * Whether this is altervista.
     */
    public static function isAltervista(): bool
    {
        return Magic::$altervista;
    }
    /**
     * Accesses a private variable from an object.
     *
     * @internal
     *
     * @param object $obj Object
     * @param string $var Attribute name
     * @psalm-suppress InvalidScope
     * @access public
     */
    public static function &getVar(object $obj, string $var)
    {
        return Closure::bind(
            fn &() => $this->{$var},
            $obj,
            $obj::class,
        )->__invoke();
    }
    /**
     * Sets a private variable in an object.
     *
     * @internal
     *
     * @param object $obj Object
     * @param string $var Attribute name
     * @param mixed  $val Attribute value
     * @psalm-suppress InvalidScope
     * @access public
     */
    public static function setVar(object $obj, string $var, mixed &$val): void
    {
        Closure::bind(
            function () use ($var, &$val): void {
                $this->{$var} =& $val;
            },
            $obj,
            $obj::class,
        )->__invoke();
    }
    /**
     * Get absolute path to file, related to session path.
     *
     * @param string $file File
     * @internal
     */
    public static function absolute(string $file): string
    {
        if (($file[0] ?? '') !== '/' && ($file[1] ?? '') !== ':' && !\in_array(substr($file, 0, 4), ['phar', 'http'], true)) {
            $file = Magic::getcwd().DIRECTORY_SEPARATOR.$file;
        }
        return $file;
    }
    /**
     * Parse t.me link.
     *
     * @internal
     * @return array{0: bool, 1: string|int}|null
     */
    public static function parseLink(string $link): array|null
    {
        if (preg_match('@([a-z0-9_-]*)\\.(?:t|telegram)\.(?:me|dog)@', $link, $matches)) {
            if ($matches[1] !== 'www') {
                return [false, $matches[1]];
            }
        }
        // t.me/c/<channelId>
        if (preg_match('@t\.me/c/(\d+)@', $link, $matches)) {
            return [false, DialogId::fromSupergroupOrChannelId((int) $matches[1])];
        }
        // Invite links
        if (preg_match('@(?:t|telegram)\\.(?:me|dog)/(joinchat/|\+)?([a-z0-9_-]*)@i', $link, $matches)) {
            return [!!$matches[1], $matches[2]];
        }
        // Deep Link
        if (preg_match('@tg://(?:resolve|openmessage|user)\?(?:domain|userid|id)=([a-z0-9_-]+)@i', $link, $matches)) {
            return [false, (int) $matches[1]];
        }
        return null;
    }

    /**
     * Opens a file in append-only mode.
     *
     * @param string $path File path.
     */
    public static function openFileAppendOnly(string $path): File
    {
        return openFile($path, "a");
    }

    /**
     * Obtains a pipe that can be used to upload a file from a stream.
     *
     */
    public static function getStreamPipe(): Pipe
    {
        return new Pipe(512*1024);
    }
    private static ?HttpClient $client = null;
    /**
     * Provide a buffered reader for a file, URL or amp stream.
     *
     * @return Closure(int): ?string
     */
    public static function openBuffered(LocalFile|RemoteUrl|ReadableStream $stream, ?Cancellation $cancellation = null): Closure
    {
        if ($stream instanceof LocalFile) {
            $stream = openFile($stream->file, 'r');
            return static fn (int $len): ?string => $stream->read(cancellation: $cancellation, length: $len);
        }
        if ($stream instanceof RemoteUrl) {
            self::$client ??= HttpClientBuilder::buildDefault();
            $request = new Request($stream->url);
            $request->setTransferTimeout(INF);
            $request->setInactivityTimeout(INF);
            $stream = self::$client->request(
                $request,
                $cancellation
            )->getBody();
        }
        $buffer = '';
        return static function (int $len) use (&$buffer, $stream, $cancellation): ?string {
            if ($buffer === null) {
                return null;
            }
            do {
                if (\strlen($buffer) >= $len) {
                    $piece = substr($buffer, 0, $len);
                    $buffer = substr($buffer, $len);
                    return $piece;
                }
                $chunk = $stream->read($cancellation);
                if ($chunk === null) {
                    $buffer = null;
                    $stream->close();
                    return null;
                }
                $buffer .= $chunk;
            } while (true);
        };
    }

    private const BLOCKING_FUNCTIONS = [
        'file_get_contents' => 'https://github.com/amphp/file, https://github.com/amphp/http-client or $this->fileGetContents()',
        'file_put_contents' => 'https://github.com/amphp/file',
        'curl_exec' => 'https://github.com/amphp/http-client',
        'mysqli_query' => 'https://github.com/amphp/mysql',
        'mysqli_connect' => 'https://github.com/amphp/mysql',
        'mysql_connect' => 'https://github.com/amphp/mysql',
        'fopen' => 'https://github.com/amphp/file',
        'fsockopen' => 'https://github.com/amphp/socket',
        'pcntl_fork' => 'Tools::callFork',
        'sleep' => '$this->sleep()',
        'usleep' => '$this->sleep()',
        'proc_open' => 'https://github.com/amphp/process',
        'shell_exec' => 'https://github.com/amphp/process',
        'exec' => 'https://github.com/amphp/process',
    ];
    private const BLOCKING_CLASSES = [
        'pdo' => 'https://github.com/amphp/mysql',
        'mysqli' => 'https://github.com/amphp/mysql',
    ];

    private const DEPRECATED_FUNCTIONS = [
        'amp\\file\\get' => 'Amp\\File\\read',
        'amp\\file\\put' => 'Amp\\File\\write',
    ];

    private const BANNED_FILE_FUNCTIONS = [
        'amp\\file\\read',
        'amp\\file\\write',
        'amp\\file\\openFile',
    ];
    private const NO_YIELD_FUNCTIONS = [
        'onstart',
        'onupdatenewmessage',
        'onupdatenewchannelmessage',
    ];
    /**
     * Perform static analysis on a certain event handler class, to make sure it satisfies some performance requirements.
     *
     * @param class-string<EventHandler> $class Class name
     *
     * @return list<EventHandlerIssue>
     */
    public static function validateEventHandlerClass(string $class): array
    {
        if (!\extension_loaded('tokenizer')) {
            throw \danog\MadelineProto\Exception::extension('tokenizer');
        }
        $plugin = is_subclass_of($class, PluginEventHandler::class);
        $file = (new ReflectionClass($class))->getFileName();
        $code = file_get_contents($file);
        $code = (new ParserFactory)->createForNewestSupportedVersion()->parse($code);
        Assert::notNull($code);
        $traverser = new NodeTraverser;
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor(new ParentConnectingVisitor);
        $code = $traverser->traverse($code);
        $finder = new NodeFinder;

        $issues = [];

        if ($plugin) {
            $class = $finder->findInstanceOf($code, ClassLike::class);
            $class = array_filter($class, static fn (ClassLike $c): bool => $c->name !== null);
            if (\count($class) !== 1 || !$class[0] instanceof Class_) {
                $issues []= new EventHandlerIssue(
                    message: Lang::$current_lang['plugins_must_have_exactly_one_class'],
                    file: $file,
                    line: 0,
                    severe: true
                );
            }
        }

        /** @var DeclareDeclare|null $declare */
        $declare = $finder->findFirstInstanceOf($code, DeclareDeclare::class);
        if ($declare === null
            || $declare->key->name !== 'strict_types'
            || !$declare->value instanceof LNumber
            || $declare->value->value !== 1
        ) {
            $issues []= new EventHandlerIssue(
                message: Lang::$current_lang['must_have_declare_types'],
                file: $file,
                line: 0,
                severe: true
            );
        }

        /** @var FuncCall $call */
        foreach ($finder->findInstanceOf($code, FuncCall::class) as $call) {
            if (!$call->name instanceof Name) {
                continue;
            }

            $name = $call->name->toLowerString();
            if (isset(self::BLOCKING_FUNCTIONS[$name])) {
                if ($name === 'fopen' &&
                    isset($call->args[0]) &&
                    $call->args[0] instanceof Arg &&
                    $call->args[0]->value instanceof String_ &&
                    str_starts_with($call->args[0]->value->value, 'php://memory')
                ) {
                    continue;
                }
                $explanation = self::BLOCKING_FUNCTIONS[$name];
                $issues []= new EventHandlerIssue(
                    message: sprintf(Lang::$current_lang['do_not_use_blocking_function'], $name, $explanation),
                    file: $file,
                    line: $call->getStartLine(),
                    severe: true
                );
                continue;
            }

            if (isset(self::DEPRECATED_FUNCTIONS[$name])) {
                $explanation = self::DEPRECATED_FUNCTIONS[$name];
                $issues []= new EventHandlerIssue(
                    message: sprintf(Lang::$current_lang['do_not_use_deprecated_function'], $name, $explanation),
                    file: $file,
                    line: $call->getStartLine(),
                    severe: true
                );
                continue;
            }

            if ($name === 'unlink'
                && $call->args
                && $call->args[0] instanceof Arg
                && $call->args[0]->value instanceof String_
            ) {
                $arg = $call->args[0]->value->value;
                if ($arg === 'MadelineProto.log') {
                    $issues []= new EventHandlerIssue(
                        message: Lang::$current_lang['do_not_delete_MadelineProto.log'],
                        file: $file,
                        line: $call->getStartLine(),
                        severe: true
                    );
                } elseif (str_starts_with($arg, 'madeline') && str_ends_with($arg, '.phar')) {
                    $issues []= new EventHandlerIssue(
                        message: Lang::$current_lang['do_not_remove_MadelineProto.log_phar'],
                        file: $file,
                        line: $call->getStartLine(),
                        severe: true
                    );
                }
                continue;
            }

            if (\in_array($name, self::BANNED_FILE_FUNCTIONS, true)) {
                $issues []= new EventHandlerIssue(
                    message: sprintf(Lang::$current_lang['recommend_not_use_filesystem_function'], $name),
                    file: $file,
                    line: $call->getStartLine(),
                    severe: false
                );
            }
        }

        /** @var New_ $new */
        foreach ($finder->findInstanceOf($code, New_::class) as $new) {
            if (!$new->class instanceof Name) {
                continue;
            }
            $name = $new->class->toLowerString();
            if (isset(self::BLOCKING_CLASSES[$name])) {
                $explanation = self::BLOCKING_CLASSES[$name];
                $issues []= new EventHandlerIssue(
                    message: sprintf(Lang::$current_lang['do_not_use_blocking_class'], $name, $explanation),
                    file: $file,
                    line: $new->getStartLine(),
                    severe: true
                );
            }
        }

        /** @var Include_ $include */
        foreach ($finder->findInstanceOf($code, Include_::class) as $include) {
            if ($plugin) {
                $issues []= new EventHandlerIssue(
                    message: Lang::$current_lang['plugins_do_not_use_require'],
                    file: $file,
                    line: $include->getStartLine(),
                    severe: true
                );
            } elseif ($include->getAttribute('parent')) {
                $parent = $include;
                while ($parent = $parent->getAttribute('parent')) {
                    if ($parent instanceof FunctionLike) {
                        $issues []= new EventHandlerIssue(
                            message: Lang::$current_lang['do_not_use_non_root_require_in_event_handler'],
                            file: $file,
                            line: $include->getStartLine(),
                            severe: true
                        );
                        break;
                    }
                }
            }
        }

        /** @var Yield_|YieldFrom $include */
        foreach ([
            ...$finder->findInstanceOf($code, Yield_::class),
            ...$finder->findInstanceOf($code, YieldFrom::class),
        ] as $include) {
            if ($include->getAttribute('parent')) {
                $parent = $include;
                while ($parent = $parent->getAttribute('parent')) {
                    if ($parent instanceof ClassMethod
                        && $parent->isPublic()
                        && \in_array($parent->name->toLowerString(), self::NO_YIELD_FUNCTIONS, true)
                    ) {
                        $issues []= new EventHandlerIssue(
                            message: Lang::$current_lang['do_not_use_yield'],
                            file: $file,
                            line: $include->getStartLine(),
                            severe: true
                        );
                        break;
                    }
                }
            }
        }

        return $issues;
    }

    private static ?bool $canConvert = null;
    /**
     * Whether we can convert any audio/video file to a VoIP OGG OPUS file, or the files must be preconverted using @libtgvoipbot.
     */
    public static function canConvertOgg(): bool
    {
        if (self::$canConvert !== null) {
            return self::$canConvert;
        }
        try {
            Ogg::convert(new ReadableBuffer(file_get_contents(__DIR__.'/empty.wav')), new WritableBuffer);
            self::$canConvert = true;
        } catch (\Throwable $e) {
            Logger::log("An error occurred while attempting conversion: $e");
            self::$canConvert = false;
        }
        return self::$canConvert;
    }

    private static ?bool $canFFmpeg = null;
    /**
     * Whether we can convert any audio/video file using ffmpeg.
     */
    public static function canUseFFmpeg(?Cancellation $cancellation = null): bool
    {
        if (self::$canFFmpeg !== null) {
            return self::$canFFmpeg;
        }
        try {
            self::$canFFmpeg = Process::start('ffmpeg -version', cancellation: $cancellation)->join($cancellation) === 0;
        } catch (\Throwable $e) {
            Logger::log("An error occurred while attempting conversion: $e");
            self::$canFFmpeg = false;
        }
        return self::$canFFmpeg;
    }
}
