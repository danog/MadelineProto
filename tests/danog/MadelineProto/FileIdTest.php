<?php

declare(strict_types=1);

namespace danog\MadelineProto\Test;

use CURLFile;
use danog\Decoder\FileId;
use danog\MadelineProto\EventHandler\Media\Photo;
use Generator;
use RuntimeException;

use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;

/**
 * @group session
 * @internal
 */
class FileIdTest extends MadelineTestCase
{
    /**
     * Strip file reference from file ID.
     *
     * @param string $fileId File ID
     */
    public static function stripFileReference(string $fileId): string
    {
        $res = FileId::fromBotAPI($fileId);
        $res->fileReference = '';
        $res->version = 0;
        return (string) $res;
    }

    /**
     * Asserts that two file IDs are equal.
     *
     * @param  string                                 $fileIdAstr File ID A
     * @param  string                                 $fileIdBstr File ID B
     * @param  string                                 $message    Message
     * @throws PHPUnit\Framework\AssertionFailedError
     */
    public static function assertFileIdEquals(string $fileIdAstr, string $fileIdBstr, string $message = ''): void
    {
        $fileIdAstr = self::stripFileReference($fileIdAstr);
        $fileIdBstr = self::stripFileReference($fileIdBstr);
        if ($fileIdAstr !== $fileIdBstr) {
            var_dump(FileId::fromBotAPI($fileIdAstr), FileId::fromBotAPI($fileIdBstr));
        }
        self::assertEquals($fileIdAstr, $fileIdBstr, $message);
    }

    /**
     * @param string $type Expected type
     * @param string $type Original type
     * @dataProvider provideFileIdsAndType
     */
    public function testDownload(string $type, string $fileIdStr, string $uniqueFileIdStr, array $fullInfo): void
    {
        self::$MadelineProto->logger("Trying to download $fileIdStr");
        self::$MadelineProto->downloadToFile($fileIdStr, sys_get_temp_dir()."/$fileIdStr");
        unlink(sys_get_temp_dir()."/$fileIdStr");
        $this->assertTrue(true);
    }
    /**
     * @param string $type Expected type
     * @param string $type Original type
     * @dataProvider provideFileIdsAndType
     */
    public function testResendConvert(string $type, string $fileIdStr, string $uniqueFileIdStr, array $fullInfo): void
    {
        self::$MadelineProto->logger("Trying to resend and then reconvert $fileIdStr");
        if ($type === 'profile_photo') {
            $chat = self::$MadelineProto->getPwrChat($fullInfo['chat']);
            $this->assertArrayHasKey('photo', $chat);
            $this->assertInstanceOf(Photo::class, $chat['photo']);
            if ($fullInfo['type'] === 'big') {
                // The photo, in its biggest size.
                $this->assertEquals($uniqueFileIdStr, $chat['photo']->botApiFileUniqueId);
            }

            $this->expectExceptionMessage("Chat photo file IDs can't be reused to resend chat photos, please use getPwrChat()['photo'], instead");
            // Throws before sending.
            self::$MadelineProto->messages->sendMedia(peer: getenv('DEST'), media: $fileIdStr);
        }
        // Each file is sent once (see provideFileIdsAndType()): the other file IDs of the same file (the other sizes of
        // a photo, the thumbnail of a document) are checked to be converted to the same input media.
        $key = $type === 'thumbnail' ? $fullInfo[0] : $type;
        $inputMedia = self::$MadelineProto->getFileInfo($fileIdStr)['InputMedia'];
        unset($inputMedia['id']['file_reference']);
        if (isset(self::$resent[$key])) {
            [$res, $resentInputMedia] = self::$resent[$key];
            $this->assertEquals($resentInputMedia, $inputMedia);
        } else {
            $res = self::$MadelineProto->MTProtoToBotAPI(self::$MadelineProto->messages->sendMedia(
                peer: getenv('DEST'),
                media: $fileIdStr,
            ));
            self::$resent[$key] = [$res, $inputMedia];
        }
        if ($type === 'thumbnail') {
            $this->assertArrayHasKey($fullInfo[0], $res);
            $res = $res[$fullInfo[0]];
            $this->assertArrayHasKey('thumb', $res);
            $this->assertFileIdEquals($fileIdStr, $res['thumb']['file_id']);
            $this->assertEquals($uniqueFileIdStr, $res['thumb']['file_unique_id']);

            [$type, $fileIdStr, $uniqueFileIdStr] = $fullInfo;
        } else {
            $this->assertArrayHasKey($type, $res, json_encode($res));
            $res = $res[$type];
        }

        $hasFileId = false;
        $hasFileUniqueId = false;
        $res = $type === 'photo' ? $res : [$res];
        foreach ($res as $k => $subRes) {
            $this->assertArrayHasKey('file_id', $subRes);
            $this->assertArrayHasKey('file_unique_id', $subRes);
            $hasFileId |= self::stripFileReference($fileIdStr) === self::stripFileReference($subRes['file_id']);
            $hasFileUniqueId |= $uniqueFileIdStr === $subRes['file_unique_id'];
        }

        if (\count($res) === 1) {
            $this->assertFileIdEquals($fileIdStr, $res[0]['file_id']);
            $this->assertEquals($uniqueFileIdStr, $res[0]['file_unique_id']);
        } else {
            $this->assertTrue((bool) $hasFileUniqueId);
            $this->assertTrue((bool) $hasFileId);
        }
    }

    /**
     * File IDs, from the bot API.
     *
     * The files are sent to DEST by the production bot, which the CI uses at once from many processes, all sending
     * to DEST (see tests/session_tests.sh, tests/makephar.sh): computed once for both tests (PHPUnit calls the data
     * provider of each), and testResendConvert() sends each file once, not each of its file IDs.
     */
    private static ?array $fileIds = null;

    /**
     * What testResendConvert() got sending each file, and its input media, by type.
     *
     * @var array<string, array{array, array}>
     */
    private static array $resent = [];

    public function provideFileIdsAndType(): array
    {
        return self::$fileIds ??= iterator_to_array($this->generateFileIdsAndType(), false);
    }

    private function generateFileIdsAndType(): Generator
    {
        $dest = getenv('DEST');
        foreach ($this->provideChats() as $chat) {
            $result = self::botApi('getChat', ['chat_id' => $chat])['photo'] ?? [];
            if (!$result) {
                continue;
            }
            yield [
                'profile_photo',
                $result['small_file_id'],
                $result['small_file_unique_id'],
                ['chat' => $chat, 'type' => 'small'],
            ];
            yield [
                'profile_photo',
                $result['big_file_id'],
                $result['big_file_unique_id'],
                ['chat' => $chat, 'type' => 'big'],
            ];
        }
        foreach ($this->provideUrls() as $type => $url) {
            $method = 'send'.str_replace('_', '', ucwords($type, '_'));
            if ($type === 'video_note') {
                // Only uploaded, not by URL.
                copy($url, basename($url));
                try {
                    $botResult = self::botApi($method, ['chat_id' => $dest, $type => new CURLFile(basename($url))]);
                } finally {
                    unlink(basename($url));
                }
            } else {
                $botResult = self::botApi($method, ['chat_id' => $dest, $type => $url]);
            }
            $botResult = $botResult[$type];
            if ($type !== 'photo') {
                $botResult = [$botResult];
            }
            foreach ($botResult as $subResult) {
                yield $full = [
                    $type,
                    $subResult['file_id'],
                    $subResult['file_unique_id'],
                    [],
                ];
                if (isset($subResult['thumb'])) {
                    yield [
                        'thumbnail',
                        $subResult['thumb']['file_id'],
                        $subResult['thumb']['file_unique_id'],
                        $full,
                    ];
                }
            }
        }
    }

    /**
     * Call a method of the bot API with BOT_TOKEN, waiting out short flood waits (HTTP 429, with retry_after).
     *
     * @return mixed The result
     */
    private static function botApi(string $method, array $params): mixed
    {
        for ($attempt = 1; ; $attempt++) {
            $handle = curl_init('https://api.telegram.org/bot'.getenv('BOT_TOKEN').'/'.$method);
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $params);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            $body = curl_exec($handle);
            if (!\is_string($body)) {
                throw new RuntimeException("Bot API $method failed: ".curl_error($handle));
            }
            $response = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
            if ($response['ok'] ?? false) {
                return $response['result'];
            }
            $retryAfter = $response['parameters']['retry_after'] ?? null;
            if (\is_int($retryAfter) && $retryAfter <= 60 && $attempt < 3) {
                sleep($retryAfter);
                continue;
            }
            // Without the URL, which has the token.
            throw new RuntimeException("Bot API $method failed: $body");
        }
    }
    public function provideChats(): array
    {
        return [getenv('DEST'), '@MadelineProto'];
    }
    public function provideUrls(): array
    {
        $res = [
            'sticker' => 'https://github.com/danog/MadelineProto/raw/v8/tests/lel.webp?raw=true',
            'photo' => 'https://github.com/danog/MadelineProto/raw/v8/tests/faust.jpg',
            'audio' => 'https://github.com/danog/MadelineProto/raw/v8/tests/mosconi.mp3?raw=true',
            'video' => 'https://github.com/danog/MadelineProto/raw/v8/tests/swing.mp4?raw=true',
            'animation' => 'https://github.com/danog/MadelineProto/raw/v8/tests/pony.mp4?raw=true',
            'document' => 'https://github.com/danog/danog.github.io/raw/master/lol/index_htm_files/0.gif',
            'voice' => 'https://daniil.it/audio_2020-02-01_18-09-08.ogg',
        ];
        if (getenv('GITHUB_SHA')) {
            $res['video_note'] = 'https://daniil.it/round.mp4';
        }
        return $res;
    }
}
