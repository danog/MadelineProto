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

use AssertionError;
use danog\MadelineProto\Exception;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\Tools;

/**
 * Represents a thumbnail inlined in a media (like the stripped preview of a photo, or the thumbnail of a secret chat media).
 *
 * It behaves like any other thumbnail, but it's downloaded from memory, without contacting Telegram:
 * its file ID carries the thumbnail itself, authenticated by the session that generated it: it's accepted by
 * every method of that session (not by the bot API, nor by other sessions).
 */
final class InlineThumbnail extends AbstractThumbnail
{
    /** @internal The type of the thumbnails of secret chat media, as in tdlib. */
    public const SECRET_CHAT_TYPE = 't';

    private const FILE_ID_PREFIX = 'inline:';
    private const FLAG_STRIPPED = 1;
    private const MAC_LENGTH = 16;
    /** Thumbnail types are letters: see https://core.telegram.org/api/files#image-thumbnail-types */
    private const TYPE_REGEX = '/^[a-zA-Z]$/D';

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
        if (!preg_match(self::TYPE_REGEX, $rawSize['type'])) {
            throw new AssertionError('Invalid thumbnail type: '.json_encode($rawSize['type']));
        }
        $bytes = (string) $rawSize['bytes'];
        $stripped = $rawSize['_'] === 'photoStrippedSize';
        $this->contents = $stripped ? Tools::inflateStripped($bytes) : $bytes;
        $this->payload = \chr($stripped ? self::FLAG_STRIPPED : 0).$rawSize['type'].$bytes;
        if ($stripped && \strlen($bytes) >= 3 && \ord($bytes[0]) === 1) {
            // In the header of stripped thumbnails, as read by Tools::inflateStripped().
            $rawSize['w'] = \ord($bytes[2]);
            $rawSize['h'] = \ord($bytes[1]);
        } elseif (!($rawSize['w'] ?? 0) || !($rawSize['h'] ?? 0)) {
            // From the header of the image, when the function is available (it may be disabled).
            try {
                $size = \function_exists('getimagesizefromstring') ? getimagesizefromstring($this->contents) : false;
            } catch (\Throwable) {
                // Not an image it can read
                $size = false;
            }
            [$rawSize['w'], $rawSize['h']] = $size ?: [0, 0];
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
            'file_id' => self::FILE_ID_PREFIX.Tools::base64urlEncode(self::mac($this->payload, $API->getInlineFileIdKey()).$this->payload),
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
     * Whether this is the file ID of an inline thumbnail.
     *
     * @internal
     */
    public static function isFileId(string $fileId): bool
    {
        return str_starts_with($fileId, self::FILE_ID_PREFIX);
    }

    /**
     * Unpack the file ID of an inline thumbnail into its download info.
     *
     * Returns null if it's not the file ID of an inline thumbnail, throws if it wasn't generated by the session
     * whose key is passed.
     *
     * @internal
     *
     * @return ?array{name: string, ext: string, mime: string, size: int, InputFileLocation: array, data: string}
     */
    public static function unpackFileId(string $fileId, #[\SensitiveParameter] string $key): ?array
    {
        if (!self::isFileId($fileId)) {
            return null;
        }
        $data = Tools::base64urlDecode(substr($fileId, \strlen(self::FILE_ID_PREFIX)));
        $payload = substr($data, self::MAC_LENGTH);
        if (\strlen($payload) < 2
            || !hash_equals(self::mac($payload, $key), substr($data, 0, self::MAC_LENGTH))
        ) {
            throw new Exception('This file ID of an inline thumbnail was not generated by this session');
        }
        $type = $payload[1];
        if (!preg_match(self::TYPE_REGEX, $type)) {
            throw new Exception('Invalid file ID of an inline thumbnail');
        }
        $contents = substr($payload, 2);
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
     * Get the unique file ID of the file ID of an inline thumbnail (null if it's not one).
     *
     * @internal
     */
    public static function getUniqueFileId(string $fileId, #[\SensitiveParameter] string $key): ?string
    {
        $info = self::unpackFileId($fileId, $key);
        return $info === null ? null : self::FILE_ID_PREFIX.self::hash($info['data']);
    }

    private static function mac(string $payload, #[\SensitiveParameter] string $key): string
    {
        return substr(hash_hmac('sha256', $payload, $key, true), 0, self::MAC_LENGTH);
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
