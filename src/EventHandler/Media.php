<?php declare(strict_types=1);

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

namespace danog\MadelineProto\EventHandler;

use Amp\ByteStream\ReadableStream;
use Amp\Cancellation;
use danog\MadelineProto\EventHandler\Media\AbstractThumbnail;
use danog\MadelineProto\EventHandler\Media\InlineThumbnail;
use danog\MadelineProto\EventHandler\Media\Thumbnail;
use danog\MadelineProto\EventHandler\Media\VideoThumbnail;
use danog\MadelineProto\Ipc\IpcCapable;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\ResumableStream;
use danog\MadelineProto\TL\Types\Bytes;
use danog\MadelineProto\Tools;
use JsonSerializable;

/**
 * Represents a generic media.
 */
abstract class Media extends IpcCapable implements JsonSerializable
{
    /** Media filesize */
    public readonly int $size;

    /** Media file name */
    public readonly string $fileName;

    /** Media file extension */
    public readonly string $fileExt;

    /** Media creation date */
    public readonly int $creationDate;

    /** Media MIME type */
    public readonly string $mimeType;

    /** Time-to-live of media */
    public readonly ?int $ttl;

    /**
     * Thumbnails that can be downloaded, from the smallest to the biggest.
     *
     * For photos, these are the smaller sizes of the photo.
     *
     * @var list<Thumbnail>
     */
    public readonly array $thumbs;

    /**
     * Animated thumbnails (MPEG4 videos), from the smallest to the biggest.
     *
     * @var list<VideoThumbnail>
     */
    public readonly array $videoThumbs;

    /** Small inline thumbnail, sent along with the media: always the only thumbnail of secret chat media. */
    public readonly ?InlineThumbnail $inlineThumb;

    /** Whether the media should be hidden behind a spoiler */
    public readonly bool $spoiler;

    /** File ID in bot API format (always present even for users) */
    public readonly string $botApiFileId;

    /** Unique file ID in bot API format (always present even for users) */
    public readonly string $botApiFileUniqueId;

    /** @internal Media location */
    public readonly array $location;

    /** @internal Encryption key for secret chat files */
    public readonly ?string $key;
    /** @internal Encryption IV for secret chat files */
    public readonly ?string $iv;
    /** @internal Encryption key fingerprint for secret chat files */
    protected readonly ?int $keyFingerprint;

    /** Whether this media originates from a secret chat. */
    public readonly bool $encrypted;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMedia,

        /** Whether this media is protected */
        public readonly bool $protected
    ) {
        parent::__construct($API);
        if ($rawMedia['secret'] ?? false) {
            $rawMedia = $rawMedia['document'];
        }
        [
            'name' => $name,
            'ext' => $this->fileExt,
            'mime' => $this->mimeType,
            'size' => $this->size,
            'InputFileLocation' => $this->location
        ] = $this->extractDownloadInfo($API, $rawMedia);
        $this->fileName = $name.$this->fileExt;

        [
            'file_id' => $this->botApiFileId,
            'file_unique_id' => $this->botApiFileUniqueId
        ] = $this->extractBotApiFile($API, $rawMedia);

        $this->creationDate = ($rawMedia['document'] ?? $rawMedia['photo'] ?? $rawMedia)['date'];
        $this->ttl = $rawMedia['ttl_seconds'] ?? null;
        $this->spoiler = $rawMedia['spoiler'] ?? false;
        $this->keyFingerprint = $rawMedia['file']['key_fingerprint'] ?? null;
        $this->key = isset($rawMedia['key']) ? (string) $rawMedia['key'] : null;
        $this->iv = isset($rawMedia['iv']) ? (string) $rawMedia['iv'] : null;
        $this->encrypted = isset($rawMedia['iv']);
        [$this->thumbs, $this->videoThumbs, $this->inlineThumb] = $this->extractThumbs($API, $rawMedia);
    }

    /**
     * @internal
     *
     * @return array{name: string, ext: string, mime: string, size: int, InputFileLocation: array}
     */
    protected function extractDownloadInfo(MTProto $API, array $rawMedia): array
    {
        return $API->getDownloadInfo($rawMedia);
    }

    /**
     * @internal
     *
     * @return array{file_id: string, file_unique_id: string}
     */
    protected function extractBotApiFile(MTProto $API, array $rawMedia): array
    {
        return $API->extractBotAPIFile($API->MTProtoToBotAPI($rawMedia));
    }

    /**
     * @internal
     *
     * @return array{list<Thumbnail>, list<VideoThumbnail>, ?InlineThumbnail}
     */
    protected function extractThumbs(MTProto $API, array $rawMedia): array
    {
        if (isset($rawMedia['iv'])) {
            // Secret chat media only carries an inline JPEG thumbnail.
            $thumb = (string) ($rawMedia['thumb'] ?? '');
            return [[], [], $thumb === '' ? null : new InlineThumbnail(
                null,
                new Bytes($thumb),
                $rawMedia['thumb_w'] ?? null,
                $rawMedia['thumb_h'] ?? null,
            )];
        }
        if (isset($rawMedia['photo'])) {
            $sizes = $rawMedia['photo']['sizes'];
            $videoSizes = $rawMedia['photo']['video_sizes'] ?? [];
            // The biggest size is the photo itself.
            $mainType = Tools::maxSize($sizes)['type'];
        } else {
            if ($rawMedia['_'] === 'decryptedMessageMediaExternalDocument') {
                $rawMedia = ['_' => 'messageMediaDocument', 'document' => $rawMedia + ['thumbs' => [$rawMedia['thumb']]]];
            }
            $sizes = $rawMedia['document']['thumbs'] ?? [];
            $videoSizes = $rawMedia['document']['video_thumbs'] ?? [];
            $mainType = null;
        }
        $thumbs = [];
        $videoThumbs = [];
        $inlineThumb = null;
        foreach ($sizes as $size) {
            switch ($size['_']) {
                case 'photoSize':
                case 'photoSizeProgressive':
                    if ($size['type'] !== $mainType) {
                        $thumbs[] = new Thumbnail($API, $rawMedia, $size, $this->protected);
                    }
                    break;
                case 'photoStrippedSize':
                    $inlineThumb = new InlineThumbnail($size['type'], new Bytes(Tools::inflateStripped((string) $size['bytes'])));
                    break;
                case 'photoCachedSize':
                    $inlineThumb ??= new InlineThumbnail($size['type'], new Bytes((string) $size['bytes']), $size['w'], $size['h']);
                    break;
            }
        }
        foreach ($videoSizes as $size) {
            if ($size['_'] === 'videoSize') {
                $videoThumbs[] = new VideoThumbnail($API, $rawMedia, $size, $this->protected);
            }
        }
        $cmp = static fn (AbstractThumbnail $a, AbstractThumbnail $b): int => $a->width*$a->height <=> $b->width*$b->height;
        usort($thumbs, $cmp);
        usort($videoThumbs, $cmp);
        return [$thumbs, $videoThumbs, $inlineThumb];
    }

    /**
     * Gets a download link for any file up to 4GB.
     *
     * @param string|null $scriptUrl Optional path to custom download script (not needed when running via web)
     */
    public function getDownloadLink(?string $scriptUrl = null): string
    {
        return $this->getClient()->getDownloadLink($this, $scriptUrl);
    }

    /**
     * Get a readable amp stream with the file contents.
     *
     * The returned stream is resumable: it can be serialized, and once unserialized,
     * reading continues from the end of the last chunk read before serialization.
     *
     * @param (callable(float, float, float): void)|null $cb Progress callback
     */
    public function getStream(?callable $cb = null, int $offset = 0, int $end = -1, ?Cancellation $cancellation = null): ReadableStream&ResumableStream
    {
        return $this->getClient()->downloadToReturnedStream($this, $cb, $offset, $end, $cancellation);
    }

    /**
     * Download the media to working directory or passed path.
     *
     * @param string $dir Directory where to download the file
     * @param (callable(float, float, float): void)|null $cb Progress callback
     */
    public function downloadToDir(?string $dir = null, ?callable $cb = null, ?Cancellation $cancellation = null): string
    {
        $dir ??= getcwd();
        return $this->getClient()->downloadToDir($this, $dir, $cb, $cancellation);
    }
    /**
     * Download the media to file.
     *
     * @param string $file Downloaded file path
     * @param (callable(float, float, float): void)|null $cb Progress callback
     */
    public function downloadToFile(string $file, ?callable $cb = null, ?Cancellation $cancellation = null): string
    {
        return $this->getClient()->downloadToFile($this, $file, $cb, $cancellation);
    }

    /**
     * @return array{
     *      ext: string,
     *      name: string,
     *      mime: string,
     *      size: int,
     *      InputFileLocation: array,
     *      key_fingerprint?: string,
     *      key?: string,
     *      iv?: string,
     * }
     *
     * @psalm-mutation-free
     */
    public function getDownloadInfo(): array
    {
        $result = [
            'name' => basename($this->fileName, $this->fileExt),
            'ext' => $this->fileExt,
            'mime' => $this->mimeType,
            'size' => $this->size,
            'InputFileLocation' => $this->location,
        ];
        if ($this->key !== null) {
            $result['key_fingerprint'] = $this->keyFingerprint;
            $result['key'] = $this->key;
            $result['iv'] = $this->iv;
        }
        return $result;
    }
    /**
     * @internal
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        $v = get_object_vars($this);
        $v['_'] = static::class;
        unset($v['API'], $v['session'], $v['location'], $v['key'], $v['iv'], $v['keyFingerprint']);
        return $v;
    }
}
