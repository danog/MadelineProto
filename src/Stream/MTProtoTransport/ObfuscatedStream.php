<?php

declare(strict_types=1);

/**
 * Obfuscated2 stream wrapper.
 *
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

namespace danog\MadelineProto\Stream\MTProtoTransport;

use danog\MadelineProto\Exception;
use danog\MadelineProto\Stream\BufferedProxyStreamInterface;
use danog\MadelineProto\Stream\Common\CtrStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Tools;

/**
 * Obfuscated2 stream wrapper.
 *
 * Manages obfuscated2 encryption/decryption
 *
 * @author Daniil Gentili <daniil@daniil.it>
 *
 * @implements BufferedProxyStreamInterface<array{secret?: string, address?: string, port?: int}>
 */
final class ObfuscatedStream extends CtrStream implements BufferedProxyStreamInterface
{
    /**
     * Maximum length of the domain of a fake TLS secret, as in TDLib, to fit in a ClientHello.
     */
    public const MAX_DOMAIN_LENGTH = 182;
    private $stream;
    private $extra;
    /**
     * Connect to stream.
     *
     * @param ConnectionContext $ctx The connection context
     */
    #[\Override]
    public function connect(ConnectionContext $ctx, string $header = ''): void
    {
        if (isset($this->extra['address'])) {
            $ctx = $ctx->clone();
            $ctx->setUri('tcp://'.$this->extra['address'].':'.$this->extra['port']);
        }
        do {
            $random = Tools::random(64);
        } while (\in_array(substr($random, 0, 4), ['PVrG', 'GET ', 'POST', 'HEAD', str_repeat(\chr(238), 4), str_repeat(\chr(221), 4)], true) || $random[0] === \chr(0xef) || substr($random, 4, 4) === "\0\0\0\0");
        if (\strlen($header) === 1) {
            $header = str_repeat($header, 4);
        }
        $random = substr_replace($random, $header.substr($random, 56 + \strlen($header)), 56);
        $random = substr_replace($random, pack('s', $ctx->getDc()).substr($random, 60 + 2), 60);
        $reversed = strrev($random);
        $key = substr($random, 8, 32);
        $keyRev = substr($reversed, 8, 32);
        if (isset($this->extra['secret'])) {
            $key = hash('sha256', $key.$this->extra['secret'], true);
            $keyRev = hash('sha256', $keyRev.$this->extra['secret'], true);
        }
        $iv = substr($random, 40, 16);
        $ivRev = substr($reversed, 40, 16);
        parent::setExtra(['encrypt' => ['key' => $key, 'iv' => $iv], 'decrypt' => ['key' => $keyRev, 'iv' => $ivRev]]);
        parent::connect($ctx);
        $random = substr_replace($random, substr($this->getEncryptor()->encrypt($random), 56, 8), 56, 8);
        $this->getStream()->write($random);
    }
    /**
     * Set extra.
     *
     * @psalm-external-mutation-free
     */
    #[\Override]
    public function setExtra($extra): void
    {
        if (isset($extra['secret'])) {
            $extra['secret'] = self::decodeSecret($extra['secret'])['key'];
        }
        $this->extra = $extra;
    }

    /**
     * Decode an MTProxy secret: raw, or hex or (URL-safe, as in proxy links) base64 encoded.
     *
     * Returns its 16-byte key, whether the transport must be padded (dd and ee secrets), and the
     * domain that fake TLS secrets (ee) append to the key.
     *
     * @return array{key: string, padded: bool, domain: ?string}
     *
     * @psalm-pure
     */
    public static function decodeSecret(#[\SensitiveParameter] string $secret): array
    {
        if (\strlen($secret) !== 16) {
            $decoded = preg_match('/^(?:[a-f0-9]{2})+$/i', $secret)
                ? hex2bin($secret)
                : base64_decode(strtr($secret, '-_', '+/'), true);
            if ($decoded === false) {
                throw new Exception('The MTProxy secret is neither hex nor base64');
            }
            $secret = $decoded;
        }
        if (\strlen($secret) === 16) {
            return ['key' => $secret, 'padded' => false, 'domain' => null];
        }
        if (\strlen($secret) === 17 && $secret[0] === "\xdd") {
            return ['key' => substr($secret, 1), 'padded' => true, 'domain' => null];
        }
        if (\strlen($secret) >= 18 && $secret[0] === "\xee") {
            if (\strlen($secret) > 17 + self::MAX_DOMAIN_LENGTH) {
                throw new Exception('The domain of the fake TLS MTProxy secret is too long');
            }
            return ['key' => substr($secret, 1, 16), 'padded' => true, 'domain' => substr($secret, 17)];
        }
        throw new Exception('Invalid MTProxy secret: it must be 16 bytes long, 17 starting with dd, or start with ee followed by the key and a domain');
    }
    /**
     * @psalm-pure
     */
    #[\Override]
    public static function getName(): string
    {
        return self::class;
    }
}
