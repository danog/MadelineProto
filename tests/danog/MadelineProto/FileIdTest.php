<?php

namespace danog\MadelineProto\Test;

use CURLFile;
use danog\Decoder\FileId;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use PHPUnit\Framework\TestCase;

class FileIdTest extends TestCase
{
    /**
     * MadelineProto instance.
     *
     * @var API
     */
    protected static $MadelineProto;

    /**
     * Setup MadelineProto instance.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        self::$MadelineProto = new API(
            'testing.madeline',
            [
                'app_info' => [
                    'api_id' => \getenv('API_ID'),
                    'api_hash' => \getenv('API_HASH'),
                ],
                'logger' => [
                    'logger' => Logger::FILE_LOGGER,
                    'logger_param' => __DIR__.'/../../MadelineProto.log',
                    'logger_level' => Logger::ULTRA_VERBOSE
                ]
            ]
        );
        self::$MadelineProto->botLogin(\getenv('BOT_TOKEN'));
    }

    /**
     * Teardown.
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        self::$MadelineProto = null;
    }

    /**
     * Strip file reference from file ID.
     *
     * @param string $fileId File ID
     *
     * @return string
     */
    public static function stripFileReference(string $fileId): string
    {
        return FileId::fromBotAPI($fileId)->setFileReference('');
    }
    /**
     * Strip access hash (and possibly ID) from file ID.
     *
     * @param string $fileId File ID
     *
     * @return string
     */
    public static function stripForChat(string $fileId): string
    {
        return FileId::fromBotAPI($fileId)->setAccessHash(0);
    }

    /**
     * Asserts that two file IDs are equal.
     *
     * @param string $fileIdAstr File ID A
     * @param string $fileIdBstr File ID B
     * @param string $message    Message
     *
     * @throws PHPUnit\Framework\AssertionFailedError
     *
     * @return void
     */
    public static function assertFileIdEquals(string $fileIdAstr, string $fileIdBstr, $message = '')
    {
        $fileIdAstr = self::stripFileReference($fileIdAstr);
        $fileIdBstr = self::stripFileReference($fileIdBstr);
        if ($fileIdAstr !== $fileIdBstr) {
            \var_dump(FileId::fromBotAPI($fileIdAstr), FileId::fromBotAPI($fileIdBstr));
        }
        self::assertEquals($fileIdAstr, $fileIdBstr, $message);
    }

    /**
     * @param string $fileId File ID
     * @param string $type   Expected type
     * @param string $type   Original type
     *
     * @dataProvider provideFileIdsAndType
     */
    public function testDownload(string $type, string $fileIdStr, string $uniqueFileIdStr, array $fullInfo)
    {
        self::$MadelineProto->logger("Trying to download $fileIdStr");
        self::$MadelineProto->downloadToFile($fileIdStr, '/dev/null');
        $this->assertTrue(true);
    }
    /**
     * @param string $fileId File ID
     * @param string $type   Expected type
     * @param string $type   Original type
     *
     * @dataProvider provideFileIdsAndType
     */
    public function testResendConvert(string $type, string $fileIdStr, string $uniqueFileIdStr, array $fullInfo)
    {
        self::$MadelineProto->logger("Trying to resend and then reconvert $fileIdStr");
        if ($type === 'profile_photo') {
            $chat = self::$MadelineProto->getPwrChat($fullInfo['chat']);
            $this->assertArrayHasKey('photo', $chat);
            $chat = $chat['photo'];
            $this->assertArrayHasKey($fullInfo['type'].'_file_id', $chat);
            $this->assertArrayHasKey($fullInfo['type'].'_file_unique_id', $chat);

            $chat[$fullInfo['type'].'_file_id'] = self::stripForChat($chat[$fullInfo['type'].'_file_id']);

            $this->assertFileIdEquals($fileIdStr, $chat[$fullInfo['type'].'_file_id']);
            $this->assertEquals($uniqueFileIdStr, $chat[$fullInfo['type'].'_file_unique_id']);

            $this->expectExceptionMessage("Chat photo file IDs can't be reused to resend chat photos, please use getPwrChat()['photo'], instead");
        }
        $res = self::$MadelineProto->messages->sendMedia(
            [
                'peer' => \getenv('DEST'),
                'media' => $fileIdStr
            ],
            [
                'botAPI' => true
            ]
        );
        if ($type === 'thumbnail') {
            $this->assertArrayHasKey($fullInfo[0], $res);
            $res = $res[$fullInfo[0]];
            $this->assertArrayHasKey('thumb', $res);
            $this->assertFileIdEquals($fileIdStr, $res['thumb']['file_id']);
            $this->assertEquals($uniqueFileIdStr, $res['thumb']['file_unique_id']);

            list($type, $fileIdStr, $uniqueFileIdStr) = $fullInfo;
        } else {
            $this->assertArrayHasKey($type, $res);
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

    public function provideFileIdsAndType(): \Generator
    {
        $dest = \getenv('DEST');
        $token = \getenv('BOT_TOKEN');
        foreach ($this->provideChats() as $chat) {
            $result = \json_decode(\file_get_contents("https://api.telegram.org/bot$token/getChat?chat_id=$chat"), true)['result']['photo'] ?? [];
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
            if ($type === 'video_note') {
                \copy($url, \basename($url));

                $handle = \curl_init("https://api.telegram.org/bot$token/sendVideoNote?chat_id=$dest");
                \curl_setopt($handle, CURLOPT_POST, true);
                \curl_setopt($handle, CURLOPT_POSTFIELDS, [
                    $type => new CURLFile(\basename($url))
                ]);
                \curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
                $botResult = \json_decode(\curl_exec($handle), true);
                \curl_close($handle);

                \unlink(\basename($url));
            } else {
                $botResult = \json_decode(\file_get_contents("https://api.telegram.org/bot$token/send$type?chat_id=$dest&$type=$url"), true);
            }
            $botResult = $botResult['result'][$type];
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
        return $result;
    }
    public function provideChats(): array
    {
        return [\getenv('DEST'), '@MadelineProto', -559184257];
    }
    public function provideUrls(): array
    {
        $res = [
            'sticker' => 'https://github.com/danog/MadelineProto/blob/master/tests/lel.webp?raw=true',
            'photo' => 'https://github.com/danog/MadelineProto/blob/master/tests/faust.jpg',
            'audio' => 'https://github.com/danog/MadelineProto/blob/master/tests/mosconi.mp3?raw=true',
            'video' => 'https://github.com/danog/MadelineProto/blob/master/tests/swing.mp4?raw=true',
            'animation' => 'https://github.com/danog/MadelineProto/blob/master/tests/pony.mp4?raw=true',
            'document' => 'https://github.com/danog/danog.github.io/raw/master/lol/index_htm_files/0.gif',
            'voice' => 'https://daniil.it/audio_2020-02-01_18-09-08.ogg',
        ];
        if (\getenv('GITHUB_SHA')) {
            $res['video_note'] = 'https://daniil.it/round.mp4';
        }
        return $res;
    }
}
