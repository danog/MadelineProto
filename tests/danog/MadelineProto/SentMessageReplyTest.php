<?php

declare(strict_types=1);

namespace danog\MadelineProto\Test;

use danog\MadelineProto\MTProto;
use PHPUnit\Framework\TestCase;

/** @internal */
class SentMessageReplyTest extends TestCase
{
    private static function getId(mixed $peer): int
    {
        return match ($peer['_']) {
            'inputPeerUser' => $peer['user_id'],
            'inputPeerChannel' => -1000000000000 - $peer['channel_id'],
        };
    }

    public function testReplyToMessage(): void
    {
        $header = MTProto::inputReplyToHeader(
            ['_' => 'inputReplyToMessage', 'reply_to_msg_id' => 5],
            false,
            self::getId(...),
        );
        $this->assertSame('messageReplyHeader', $header['_']);
        $this->assertSame(5, $header['reply_to_msg_id']);
        $this->assertFalse($header['reply_to_scheduled']);
        $this->assertFalse($header['forum_topic']);
        $this->assertFalse($header['quote']);
        $this->assertArrayNotHasKey('reply_to_top_id', $header);
        $this->assertArrayNotHasKey('reply_to_peer_id', $header);
    }

    public function testReplyInTopicWithQuote(): void
    {
        $header = MTProto::inputReplyToHeader(
            [
                '_' => 'inputReplyToMessage',
                'reply_to_msg_id' => 5,
                'top_msg_id' => 3,
                'reply_to_peer_id' => ['_' => 'inputPeerChannel', 'channel_id' => 1234, 'access_hash' => 1],
                'quote_text' => 'quote',
                'quote_entities' => [],
                'quote_offset' => 2,
            ],
            true,
            self::getId(...),
        );
        $this->assertSame(5, $header['reply_to_msg_id']);
        $this->assertSame(3, $header['reply_to_top_id']);
        $this->assertTrue($header['forum_topic']);
        $this->assertSame(-1000000001234, $header['reply_to_peer_id']);
        $this->assertTrue($header['quote']);
        $this->assertSame('quote', $header['quote_text']);
        $this->assertSame([], $header['quote_entities']);
        $this->assertSame(2, $header['quote_offset']);
    }

    public function testTopicRoot(): void
    {
        $header = MTProto::inputReplyToHeader(
            ['_' => 'inputReplyToMessage', 'reply_to_msg_id' => 0, 'top_msg_id' => 3],
            true,
            self::getId(...),
        );
        $this->assertSame(3, $header['reply_to_msg_id']);
        $this->assertArrayNotHasKey('reply_to_top_id', $header);
    }

    public function testReplyToStory(): void
    {
        $this->assertSame(
            ['_' => 'messageReplyStoryHeader', 'peer' => 101374607, 'story_id' => 7],
            MTProto::inputReplyToHeader(
                ['_' => 'inputReplyToStory', 'peer' => ['_' => 'inputPeerUser', 'user_id' => 101374607, 'access_hash' => 1], 'story_id' => 7],
                false,
                self::getId(...),
            ),
        );
    }

    public function testUnconvertible(): void
    {
        $this->assertNull(MTProto::inputReplyToHeader(['_' => 'inputReplyToEphemeralMessage', 'id' => 1], false, self::getId(...)));
    }
}
