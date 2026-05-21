<?php

declare(strict_types=1);

/**
 * MTProxy secret parser.
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

/**
 * Parses MTProxy secrets in hex and base64url form.
 *
 * @internal
 */
final class MTProxySecretParser
{
    /**
     * @return array{secret: string, domain?: string, padded?: true}|null
     */
    public static function parse(string $secret): ?array
    {
        if ($secret === '') {
            return null;
        }

        $raw = match (true) {
            self::isHexSecret($secret) => hex2bin($secret),
            self::isBase64UrlSecret($secret) => self::decodeBase64Url($secret),
            default => $secret,
        };

        if ($raw === false || $raw === '') {
            return null;
        }

        return self::parseRaw($raw);
    }

    /**
     * @return array{secret: string, domain?: string, padded?: true}|null
     */
    private static function parseRaw(string $raw): ?array
    {
        $length = \strlen($raw);
        if ($length === 16) {
            return ['secret' => $raw];
        }

        if ($length === 17 && $raw[0] === "\xdd") {
            return [
                'secret' => substr($raw, 1, 16),
                'padded' => true,
            ];
        }

        if ($length >= 21 && $raw[0] === "\xee") {
            $secret = substr($raw, 1, 16);
            $domain = substr($raw, 17);
            if (\strlen($secret) !== 16 || $domain === '') {
                return null;
            }

            return [
                'secret' => $secret,
                'domain' => $domain,
            ];
        }

        return null;
    }

    private static function isHexSecret(string $secret): bool
    {
        $length = \strlen($secret);
        return $length >= 32 && ($length % 2) === 0 && ctype_xdigit($secret);
    }

    private static function isBase64UrlSecret(string $secret): bool
    {
        $length = \strlen($secret);
        if ($length < 22 || ($length % 4) === 1) {
            return false;
        }

        $inner = rtrim($secret, '=');
        return $inner !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $inner) === 1;
    }

    private static function decodeBase64Url(string $secret): string|false
    {
        $secret = rtrim($secret, '=');
        $secret .= str_repeat('=', (4 - \strlen($secret) % 4) % 4);

        return base64_decode(strtr($secret, '-_', '+/'), true);
    }
}
