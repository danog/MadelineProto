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
     * @param string $fileId File ID
     * @param string $type   Expected type
     *
     * @dataProvider provideFileIdsAndType
     */
    public function testDownload(string $type, string $fileIdStr, string $uniqueFileIdStr)
    {
        self::$MadelineProto->logger("Trying to download $fileIdStr");
        self::$MadelineProto->downloadToFile($fileIdStr, '/dev/null');
        $this->assertTrue(true);
    }
    /**
     * @param string $fileId File ID
     * @param string $type   Expected type
     *
     * @dataProvider provideFileIdsAndType
     */
    public function testResend(string $type, string $fileIdStr, string $uniqueFileIdStr)
    {
        self::$MadelineProto->logger("Trying to resend $fileIdStr");
        self::$MadelineProto->messages->sendMedia(
            [
                'peer' => \getenv('DEST'),
                'media' => $fileIdStr
            ]
        );
        $this->assertTrue(true);
    }

    public function provideFileIdsAndType(): \Generator
    {
        $dest = \getenv('DEST');
        $token = \getenv('BOT_TOKEN');
        foreach ($this->provideChats() as $chat) {
            $result = \json_decode(\file_get_contents("https://api.telegram.org/bot$token/getChat?chat_id=$chat"), true)['result']['photo'];
            yield [
                'profile_photo',
                $result['small_file_id'],
                $result['small_file_unique_id'],
            ];
            yield [
                'profile_photo',
                $result['big_file_id'],
                $result['big_file_unique_id'],
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
                yield [
                    $type,
                    $subResult['file_id'],
                    $subResult['file_unique_id']
                ];
                if (isset($subResult['thumb'])) {
                    yield [
                        'thumbnail',
                        $subResult['thumb']['file_id'],
                        $subResult['thumb']['file_unique_id']
                    ];
                }
            }
        }
        return $result;
    }
    public function provideChats(): array
    {
        return [\getenv('DEST'), '@MadelineProto'];
    }
    public function provideUrls(): array
    {
        return [
            'sticker' => 'https://github.com/danog/MadelineProto/blob/master/tests/lel.webp?raw=true',
            'photo' => 'https://github.com/danog/MadelineProto/blob/master/tests/faust.jpg',
            'audio' => 'https://github.com/danog/MadelineProto/blob/master/tests/mosconi.mp3?raw=true',
            'video' => 'https://github.com/danog/MadelineProto/blob/master/tests/swing.mp4?raw=true',
            'animation' => 'https://github.com/danog/MadelineProto/blob/master/tests/pony.mp4?raw=true',
            'document' => 'https://github.com/danog/danog.github.io/raw/master/lol/index_htm_files/0.gif',
            'voice' => 'https://daniil.it/audio_2020-02-01_18-09-08.ogg',
            //'video_note' => 'https://daniil.it/round.mp4'
        ];
    }
}
