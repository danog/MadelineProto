<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Mahdi <mahdi.talaee1379@gmail.com>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Media;

use danog\MadelineProto\MTProto;
use danog\MadelineProto\Tools;

/**
 * Represents a thumbnail inlined in a media (like the stripped preview of a photo, or the thumbnail of a secret chat media).
 *
 * It behaves like any other thumbnail, but it's downloaded from memory, without contacting Telegram:
 * its file ID (accepted by every MadelineProto method, not by the bot API) carries the thumbnail itself.
 */
final class InlineThumbnail extends AbstractThumbnail
{
    /** @internal The type of the thumbnails of secret chat media, as in tdlib. */
    public const SECRET_CHAT_TYPE = 't';

    private const FILE_ID_PREFIX = 'inline:';
    private const FLAG_STRIPPED = 1;

    /** @internal The thumbnail itself */
    protected readonly string $contents;

    /** @internal What the file ID carries (stripped thumbnails are inflated when downloaded) */
    private readonly string $payload;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMedia,
        array $rawSize,
        bool $protected,
    ) {
        $bytes = (string) $rawSize['bytes'];
        $stripped = $rawSize['_'] === 'photoStrippedSize';
        $this->contents = $stripped ? Tools::inflateStripped($bytes) : $bytes;
        $this->payload = \chr($stripped ? self::FLAG_STRIPPED : 0).\chr(\strlen($rawSize['type'])).$rawSize['type'].$bytes;
        if (!($rawSize['w'] ?? 0) || !($rawSize['h'] ?? 0)) {
            [$rawSize['w'], $rawSize['h']] = @getimagesizefromstring($this->contents) ?: [0, 0];
        }
        parent::__construct($API, $rawMedia, $rawSize, $protected);
    }

    #[\Override]
    protected function extractFormat(array $rawMedia): array
    {
        return self::getFormat($this->contents);
    }

    #[\Override]
    protected function extractDownloadInfo(MTProto $API, array $rawMedia): array
    {
        $id = ($rawMedia['photo'] ?? $rawMedia['document'] ?? $rawMedia['file'] ?? [])['id'] ?? null;
        [$ext, $mime] = $this->extractFormat($rawMedia);
        return [
            'name' => ($id === null ? self::hash($this->contents) : Tools::unpackSignedLongString($id)).'_'.$this->type,
            'ext' => $ext,
            'mime' => $mime,
            'size' => \strlen($this->contents),
            'InputFileLocation' => [],
        ];
    }

    #[\Override]
    protected function extractBotApiFile(MTProto $API, array $rawMedia): array
    {
        return [
            'file_id' => self::FILE_ID_PREFIX.Tools::base64urlEncode($this->payload),
            'file_unique_id' => self::FILE_ID_PREFIX.self::hash($this->contents),
        ];
    }

    #[\Override]
    public function getDownloadInfo(): array
    {
        $result = parent::getDownloadInfo();
        $result['data'] = $this->contents;
        return $result;
    }

    /**
     * Unpack the file ID of an inline thumbnail into its download info.
     *
     * @internal
     *
     * @return ?array{name: string, ext: string, mime: string, size: int, InputFileLocation: array, data: string}
     */
    public static function unpackFileId(string $fileId): ?array
    {
        if (!str_starts_with($fileId, self::FILE_ID_PREFIX)) {
            return null;
        }
        $payload = Tools::base64urlDecode(substr($fileId, \strlen(self::FILE_ID_PREFIX)));
        $type = substr($payload, 2, \ord($payload[1]));
        $contents = substr($payload, 2 + \strlen($type));
        if (\ord($payload[0]) & self::FLAG_STRIPPED) {
            $contents = Tools::inflateStripped($contents);
        }
        [$ext, $mime] = self::getFormat($contents);
        return [
            'name' => self::hash($contents).'_'.$type,
            'ext' => $ext,
            'mime' => $mime,
            'size' => \strlen($contents),
            'InputFileLocation' => [],
            'data' => $contents,
        ];
    }

    /**
     * Get the unique file ID of the file ID of an inline thumbnail.
     *
     * @internal
     */
    public static function getUniqueFileId(string $fileId): ?string
    {
        $info = self::unpackFileId($fileId);
        return $info === null ? null : self::FILE_ID_PREFIX.self::hash($info['data']);
    }

    private static function hash(string $contents): string
    {
        return bin2hex(substr(hash('sha256', $contents, true), 0, 16));
    }

    /** @return array{string, string} */
    private static function getFormat(string $contents): array
    {
        return substr($contents, 0, 4) === 'RIFF' && substr($contents, 8, 4) === 'WEBP'
            ? ['.webp', 'image/webp']
            : ['.jpg', 'image/jpeg'];
    }

    /** @internal */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        $v = parent::jsonSerialize();
        unset($v['contents']);
        return $v;
    }
}
