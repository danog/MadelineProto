<?php

declare(strict_types=1);

/**
 * MCP update queue.
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

namespace danog\MadelineProto\Mcp;

use Amp\File\Whence;

use function Amp\File\exists;
use function Amp\File\getSize;
use function Amp\File\openFile;

/**
 * @internal
 */
final class UpdateQueue
{
    public static function path(string $session): string
    {
        return $session.DIRECTORY_SEPARATOR.'mcp-updates.jsonl';
    }

    public static function enabledPath(string $session): string
    {
        return $session.DIRECTORY_SEPARATOR.'mcp-updates.enabled';
    }

    public static function enable(string $session): void
    {
        $file = openFile(self::enabledPath($session), 'ab');
        $file->close();
    }

    public static function enabled(string $session): bool
    {
        return exists(self::enabledPath($session));
    }

    public static function push(string $session, array $update): void
    {
        $file = openFile(self::path($session), 'ab');
        try {
            $file->write(json_encode([
                'time' => time(),
                'update' => $update,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
        } finally {
            $file->close();
        }
    }

    public static function size(string $session): int
    {
        $path = self::path($session);
        return exists($path) ? getSize($path) : 0;
    }

    /** @return list<array> */
    public static function pull(string $session, int &$offset): array
    {
        $path = self::path($session);
        if (!exists($path)) {
            return [];
        }
        $size = getSize($path);
        if ($offset > $size) {
            $offset = 0;
        }
        if ($offset === $size) {
            return [];
        }
        $file = openFile($path, 'rb');
        try {
            $file->seek($offset, Whence::Start);
            $data = '';
            while (($chunk = $file->read()) !== null) {
                $data .= $chunk;
            }
            $offset = $file->tell();
        } finally {
            $file->close();
        }

        $updates = [];
        foreach (explode("\n", trim($data)) as $line) {
            if ($line === '') {
                continue;
            }
            $decoded = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
            if (\is_array($decoded)) {
                $updates[] = $decoded;
            }
        }
        return $updates;
    }
}
