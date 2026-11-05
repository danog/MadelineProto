<?php

declare(strict_types=1);

namespace danog\MadelineProto\Test;

use danog\MadelineProto\MTProto;
use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\TL\TL;
use danog\MadelineProto\TL\Types\Bytes;
use PHPUnit\Framework\TestCase;

/** @internal */
class SentMessageReplyTest extends TestCase
{
    private static function getId(mixed $peer): int
    {
        return match ($peer['_']) {
            'inputPeerUser', 'inputUser' => $peer['user_id'],
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

    /**
     * #1979: the message built from a sendMessage call had the arguments as passed, and the serializer accepts a
     * numeric string for an int, so AbstractMessage got a string reply_to_msg_id (TypeError).
     */
    public function testSerializerReturnsTheArgumentsAsSent(): void
    {
        $tl = new TL(null);
        $tl->init(new TLSchema);

        $arguments = [
            'peer' => ['_' => 'inputPeerUser', 'user_id' => '101374607', 'access_hash' => '1'],
            'message' => 1234,
            'silent' => 1,
            'reply_to' => [
                '_' => 'inputReplyToMessage',
                'reply_to_msg_id' => '5598',
                'quote_text' => 'quote',
                'quote_offset' => '2',
                'quote_entities' => [['_' => 'messageEntityMentionName', 'offset' => '0', 'length' => '5', 'user_id' => ['_' => 'inputUser', 'user_id' => '783732906', 'access_hash' => 1]]],
            ],
            'reply_markup' => ['_' => 'replyInlineMarkup', 'rows' => [['_' => 'keyboardInlineButtonRow', 'buttons' => [['_' => 'keyboardInlineButton', 'text' => 'a', 'type' => ['_' => 'inlineButtonTypeCallback', 'data' => 'b']]]]]],
            'entities' => [['_' => 'messageEntityBold', 'offset' => '0', 'length' => '4']],
            'random_id' => '1234',
        ];
        $serialized = $tl->serializeMethod('messages.sendMessage', $arguments, $sent);

        $this->assertSame(['_' => 'inputPeerUser', 'user_id' => 101374607, 'access_hash' => 1], $sent['peer']);
        $this->assertSame('1234', $sent['message']);
        $this->assertTrue($sent['silent']);
        $this->assertSame(1234, $sent['random_id']);
        $this->assertSame([['_' => 'messageEntityBold', 'offset' => 0, 'length' => 4]], $sent['entities']);
        $this->assertArrayNotHasKey('flags', $sent);
        $this->assertArrayNotHasKey('flags', $sent['reply_to']);
        $data = $sent['reply_markup']['rows'][0]['buttons'][0]['type']['data'];
        $this->assertInstanceOf(Bytes::class, $data);
        $this->assertSame('b', (string) $data);

        $header = MTProto::inputReplyToHeader($sent['reply_to'], false, self::getId(...));
        $this->assertSame(5598, $header['reply_to_msg_id']);
        $this->assertSame(2, $header['quote_offset']);
        $this->assertSame([['_' => 'messageEntityMentionName', 'offset' => 0, 'length' => 5, 'user_id' => 783732906]], $header['quote_entities']);

        // Sent again as is (a refresh of its file references)
        $this->assertSame($serialized, $tl->serializeMethod('messages.sendMessage', $sent, $again));
        $this->assertEquals($sent, $again);
    }

    public function testUnconvertible(): void
    {
        $this->assertNull(MTProto::inputReplyToHeader(['_' => 'inputReplyToEphemeralMessage', 'id' => 1], false, self::getId(...)));
    }
}
