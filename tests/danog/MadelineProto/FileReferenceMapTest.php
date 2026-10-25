<?php

declare(strict_types=1);

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

namespace danog\MadelineProto\Test;

use danog\MadelineProto\MTProtoTools\FileRef\FileRefEnvironment;
use danog\MadelineProto\MTProtoTools\FileRef\FileRefMap;
use danog\MadelineProto\MTProtoTools\FileRef\FileRefTraversal;
use danog\MadelineProto\Settings;
use danog\MadelineProto\TL\TL;
use danog\MadelineProto\TL\Types\Bytes;
use PHPUnit\Framework\TestCase;

use function Amp\async;
use function Amp\delay;
use function Amp\Future\await;

/**
 * The file reference database driven by the file reference map (src/file_ref_map.json): incoming traversal of
 * method results and updates, outgoing traversal of method calls, refresh actions.
 *
 * The objects are shaped like the ones of MadelineProto's deserializer: a Peer is its bot API ID, the IDs of
 * channels and chats are bot API IDs.
 *
 * @internal
 */
final class FileReferenceMapTest extends TestCase
{
    /**
     * The message magnaluna (8.11.1) failed to deserialize with "Trying to add origin to constructor channelFull with
     * no origin context set": an rpc_result with the messages.chatFull of channels.getFullChannel (received gzipped,
     * with layer 231). Its content, serialized with the current layer: the wire format of channelFull and channel
     * changes with the layers (232 added a flag to both), and the tests run without the schemas of past layers.
     */
    public static function channelFullPayload(): string
    {
        $TL = new TL(null);
        $TL->init((new Settings)->getSchema());
        $chatFull = [
            '_' => 'messages.chatFull',
            'full_chat' => [
                '_' => 'channelFull',
                'can_view_participants' => true,
                'has_scheduled' => true,
                'can_view_stats' => true,
                'id' => self::CHANNEL_FULL_CHANNEL,
                'about' => "Official MadelineProto channel.\nItalian Channel: @MadelineProtoIta\nGroup: @pwrtelegramgroup",
                'participants_count' => 6361,
                'admins_count' => 6,
                'read_inbox_max_id' => 777,
                'read_outbox_max_id' => 0,
                'unread_count' => 0,
                'chat_photo' => [
                    '_' => 'photo',
                    'id' => self::CHANNEL_FULL_PHOTO,
                    'access_hash' => -4922785592533685690,
                    'file_reference' => base64_decode('AGrXewVuYMOhgm4Ag+CywBex+WgI', true),
                    'date' => 1490136922,
                    'sizes' => [
                        ['_' => 'photoSize', 'type' => 'a', 'w' => 160, 'h' => 160, 'size' => 4048],
                        ['_' => 'photoSize', 'type' => 'b', 'w' => 320, 'h' => 320, 'size' => 10186],
                        ['_' => 'photoSize', 'type' => 'c', 'w' => 640, 'h' => 640, 'size' => 26635],
                    ],
                    'dc_id' => 2,
                ],
                'notify_settings' => ['_' => 'peerNotifySettings'],
                'bot_info' => [['_' => 'botInfo', 'user_id' => 5583307294]],
                'pinned_msg_id' => 762,
                'linked_chat_id' => 1375152824,
                'stats_dc' => 2,
                'pts' => 2365,
            ],
            'chats' => [[
                '_' => 'channel',
                'broadcast' => true,
                'signatures' => true,
                'has_link' => true,
                'id' => self::CHANNEL_FULL_CHANNEL,
                'access_hash' => 1,
                'title' => 'MadelineProto | Official Channel',
                'username' => 'MadelineProto',
                'photo' => ['_' => 'chatPhoto', 'photo_id' => self::CHANNEL_FULL_PHOTO, 'dc_id' => 2],
                'date' => 1696874049,
            ]],
            'users' => [],
        ];
        // rpc_result#f35c6d01 req_msg_id:long result:Object
        return pack('V', 0xf35c6d01).pack('P', 7698486276853202908).$TL->serializeObject(['type' => 'messages.ChatFull'], $chatFull, 'result');
    }
    /** The channel of that payload (MadelineProto). */
    public const CHANNEL_FULL_CHANNEL = 1115554076;
    /** Its photo. */
    public const CHANNEL_FULL_PHOTO = 1629045245737019312;

    private const SELF = 1;

    private static function photo(int $id): array
    {
        return ['_' => 'photo', 'id' => $id, 'access_hash' => 11, 'file_reference' => new Bytes("ref$id"), 'date' => 0, 'sizes' => [], 'dc_id' => 2];
    }
    private static function document(int $id, array $attributes = []): array
    {
        return ['_' => 'document', 'id' => $id, 'access_hash' => 22, 'file_reference' => new Bytes("ref$id"), 'date' => 0, 'mime_type' => 'image/webp', 'size' => 1, 'dc_id' => 2, 'attributes' => $attributes];
    }
    private static function message(int $id, int $peer, array $media, array $extra = []): array
    {
        return ['_' => 'message', 'id' => $id, 'peer_id' => $peer, 'date' => 0, 'message' => '', 'media' => $media, ...$extra];
    }
    private static function env(): TestFileRefEnvironment
    {
        return new TestFileRefEnvironment(self::SELF);
    }
    /**
     * @return array<string, list<array>> The sources committed for each FileId.
     */
    private static function sourcesOf(array $commits): array
    {
        $res = [];
        foreach ($commits as $commit) {
            $res[FileRefMap::getFileIdKey($commit['fileId'])] = $commit['sources'];
        }
        return $res;
    }
    private static function method(string $method, array $args, mixed $result, ?FileRefEnvironment $env = null): array
    {
        return FileRefTraversal::traverseMethodResult(FileRefMap::get(), $env ?? self::env(), $method, $args, $result);
    }
    private static function update(array $update): array
    {
        return FileRefTraversal::traverseUpdate(FileRefMap::get(), self::env(), $update);
    }

    public function testTheMapIsLoaded(): void
    {
        $map = FileRefMap::get();
        $this->assertSame($map, FileRefMap::get());
        // Generated for the layer of the schema (the counts below change with every layer: not checked).
        $this->assertSame((new Settings)->getSchema()->getLayer(), $map->layer);
        $this->assertNotEmpty($map->methodResults);
        $this->assertNotEmpty($map->incoming);
        $this->assertNotEmpty($map->skipped);
        $this->assertSame([
            'inputPhoto' => 'fileIdPhoto',
            'inputDocument' => 'fileIdDocument',
            'inputDocumentFileLocation' => 'fileIdDocument',
            'inputPhotoFileLocation' => 'fileIdPhoto',
        ], $map->swapLocations);
        // A refresh action for every file source of the database schema, and only for them.
        $dbSchema = json_decode(file_get_contents(__DIR__.'/../../../src/file_ref_map.json'), true, flags: JSON_THROW_ON_ERROR)['db_schema'];
        preg_match_all('/^(fileSource\w+)#/m', $dbSchema, $matches);
        $this->assertNotEmpty($matches[1]);
        $this->assertEqualsCanonicalizing($matches[1], array_keys($map->refreshActions));

        // Every file source the traversers can store can be refreshed.
        foreach ([...$map->methodResults, ...$map->incoming] as $traverser) {
            foreach ($traverser['push_sources'] as $source) {
                $this->assertArrayHasKey($source['stored_constructor'], $map->refreshActions);
            }
        }
    }

    public function testTheChannelFullOfMagnalunaIsStored(): void
    {
        $TL = new TL(null);
        $TL->init((new Settings)->getSchema());
        $message = $TL->deserialize(self::channelFullPayload(), ['type' => '', 'connection' => null, 'encrypted' => false]);
        $this->assertSame('rpc_result', $message['_']);
        $result = $message['result'];
        $this->assertSame('channelFull', $result['full_chat']['_']);
        // The deserializer turned the ID of the channel into a bot API ID.
        $this->assertSame(-1000000000000 - self::CHANNEL_FULL_CHANNEL, $result['full_chat']['id']);

        $commits = self::method('channels.getFullChannel', ['channel' => -1000000000000 - self::CHANNEL_FULL_CHANNEL], $result);
        $this->assertCount(1, $commits);
        $this->assertSame(['_' => 'fileIdPhoto', 'id' => self::CHANNEL_FULL_PHOTO], $commits[0]['fileId']);
        $this->assertSame((string) $result['full_chat']['chat_photo']['file_reference'], $commits[0]['reference']);
        // The channel ID of the API, refreshed with channels.getFullChannel.
        $this->assertSame([['_' => 'fileSourceChannelFull', 'channel' => self::CHANNEL_FULL_CHANNEL]], $commits[0]['sources']);
        $this->assertSame(
            ['channels.getFullChannel', ['channel' => ['_' => 'inputChannel', 'channel_id' => self::CHANNEL_FULL_CHANNEL, 'access_hash' => 1]]],
            FileRefMap::get()->buildRefreshCall($commits[0]['sources'][0], self::env()),
        );
    }

    public function testTraversalsHaveTheirOwnState(): void
    {
        // The state of a traversal is its own: traversals suspended at the same time (here, while resolving the
        // peers of their arguments) don't see each other's sources and parents.
        $env = new TestFileRefEnvironment(self::SELF, suspend: true);
        $traverse = static fn (int $peer, int $photo) => async(static fn () => self::method(
            'messages.getScheduledMessages',
            ['peer' => ['_' => 'inputPeerUser', 'user_id' => $peer, 'access_hash' => 0]],
            ['_' => 'messages.messages', 'messages' => [self::message(5, $peer, ['_' => 'messageMediaPhoto', 'photo' => self::photo($photo)])], 'topics' => [], 'chats' => [], 'users' => []],
            $env,
        ));
        [$a, $b] = await([$traverse(100, 1000), $traverse(200, 2000)]);
        $this->assertSame(['fileIdPhoto:1000' => [['_' => 'fileSourceScheduledMessage', 'peer' => 100, 'id' => 5]]], self::sourcesOf($a));
        $this->assertSame(['fileIdPhoto:2000' => [['_' => 'fileSourceScheduledMessage', 'peer' => 200, 'id' => 5]]], self::sourcesOf($b));
        $this->assertGreaterThan(0, $env->suspensions);
    }

    public function testAMessageInAnUpdate(): void
    {
        $commits = self::update(['_' => 'updateNewMessage', 'message' => self::message(10, -1000000001234, ['_' => 'messageMediaPhoto', 'photo' => self::photo(100)]), 'pts' => 1, 'pts_count' => 1]);
        $this->assertSame([[
            'fileId' => ['_' => 'fileIdPhoto', 'id' => 100],
            'reference' => 'ref100',
            // quick_reply_shortcut_id (paramIsFlagPassthrough) is not set: neither is the flag of the source.
            'sources' => [['_' => 'fileSourceMessage', 'peer' => -1000000001234, 'id' => 10]],
        ]], $commits);
    }

    public function testThePassthroughFlagIsCopied(): void
    {
        $commits = self::update(['_' => 'updateQuickReplyMessage', 'message' => self::message(10, 42, ['_' => 'messageMediaPhoto', 'photo' => self::photo(100)], ['quick_reply_shortcut_id' => 7])]);
        $this->assertSame(['fileIdPhoto:100' => [['_' => 'fileSourceMessage', 'quick_reply_shortcut_id' => 7, 'peer' => 42, 'id' => 10]]], self::sourcesOf($commits));
    }

    public function testAScheduledMessageInAnUpdate(): void
    {
        // Needs the updateNewScheduledMessage parent (pathParent), which excludes fileSourceMessage
        // (needs_not_parents); the sticker is also stored with its sticker set (a source of the commit location).
        $set = ['_' => 'inputStickerSetID', 'id' => 3, 'access_hash' => 4];
        $sticker = self::document(200, [['_' => 'documentAttributeSticker', 'alt' => '', 'stickerset' => $set]]);
        $commits = self::update(['_' => 'updateNewScheduledMessage', 'message' => self::message(5, 42, ['_' => 'messageMediaDocument', 'document' => $sticker])]);
        $this->assertSame(['fileIdDocument:200' => [
            ['_' => 'fileSourceScheduledMessage', 'peer' => 42, 'id' => 5],
            ['_' => 'fileSourceStickerSet', 'stickerset' => $set],
        ]], self::sourcesOf($commits));

        // An empty sticker set is not a source.
        $sticker = self::document(200, [['_' => 'documentAttributeSticker', 'alt' => '', 'stickerset' => ['_' => 'inputStickerSetEmpty']]]);
        $commits = self::update(['_' => 'updateNewScheduledMessage', 'message' => self::message(5, 42, ['_' => 'messageMediaDocument', 'document' => $sticker])]);
        $this->assertSame(['fileIdDocument:200' => [['_' => 'fileSourceScheduledMessage', 'peer' => 42, 'id' => 5]]], self::sourcesOf($commits));
    }

    public static function provideScheduledMessagesPeers(): iterable
    {
        yield 'channel' => [['_' => 'inputPeerChannel', 'channel_id' => 1234, 'access_hash' => 1], -1000000001234];
        yield 'chat' => [['_' => 'inputPeerChat', 'chat_id' => 77], -77];
        yield 'self' => [['_' => 'inputPeerSelf'], self::SELF];
        yield 'bot API ID' => [-1000000001234, -1000000001234];
        yield 'empty' => [['_' => 'inputPeerEmpty'], null];
        yield 'username' => ['@username', null];
    }

    /**
     * @dataProvider provideScheduledMessagesPeers
     */
    public function testScheduledMessagesOfAMethod(mixed $peer, ?int $expected): void
    {
        // Needs the messages.getScheduledMessages parent (extractPeerIdFromInputPeerAndStore of its peer argument).
        $commits = self::method(
            'messages.getScheduledMessages',
            ['peer' => $peer, 'id' => [5]],
            ['_' => 'messages.messages', 'messages' => [self::message(5, 42, ['_' => 'messageMediaPhoto', 'photo' => self::photo(100)])], 'topics' => [], 'chats' => [], 'users' => []],
        );
        $this->assertSame(
            // Never a fileSourceMessage: the message is scheduled.
            ['fileIdPhoto:100' => $expected === null ? [] : [['_' => 'fileSourceScheduledMessage', 'peer' => $expected, 'id' => 5]]],
            self::sourcesOf($commits),
        );
    }

    public static function provideUsers(): iterable
    {
        yield 'self' => [['_' => 'inputUserSelf'], self::SELF];
        yield 'user' => [['_' => 'inputUser', 'user_id' => 9, 'access_hash' => 0], 9];
        yield 'from message' => [['_' => 'inputUserFromMessage', 'peer' => ['_' => 'inputPeerSelf'], 'msg_id' => 1, 'user_id' => 9], 9];
        yield 'bot API ID' => [9, 9];
        yield 'empty' => [['_' => 'inputUserEmpty'], null];
        yield 'channel' => [-1000000001234, null];
    }

    /**
     * @dataProvider provideUsers
     */
    public function testUserPhotos(mixed $user, ?int $expected): void
    {
        // A source of the photo commit location, needing the photos.getUserPhotos parent.
        $commits = self::method(
            'photos.getUserPhotos',
            ['user_id' => $user, 'offset' => 0, 'max_id' => 0, 'limit' => 1],
            ['_' => 'photos.photos', 'photos' => [self::photo(100), self::photo(101)], 'users' => []],
        );
        $this->assertSame([
            'fileIdPhoto:100' => $expected === null ? [] : [['_' => 'fileSourceUserProfilePhoto', 'user_id' => $expected, 'max_id' => 100]],
            'fileIdPhoto:101' => $expected === null ? [] : [['_' => 'fileSourceUserProfilePhoto', 'user_id' => $expected, 'max_id' => 101]],
        ], self::sourcesOf($commits));
    }

    public function testTheFallbackAndTheResultOfAMethod(): void
    {
        $result = ['_' => 'photos.photo', 'photo' => self::photo(100), 'users' => []];
        // bot (paramIsFlagFallback) defaults to inputUserSelf; max_id is the ID of the photo in the result of the method.
        $commits = self::method('photos.updateProfilePhoto', ['id' => ['_' => 'inputPhoto', 'id' => 100, 'access_hash' => 11, 'file_reference' => '']], $result);
        $this->assertSame(['fileIdPhoto:100' => [['_' => 'fileSourceUserProfilePhoto', 'user_id' => self::SELF, 'max_id' => 100]]], self::sourcesOf($commits));

        $commits = self::method('photos.updateProfilePhoto', ['bot' => ['_' => 'inputUser', 'user_id' => 9, 'access_hash' => 0], 'id' => []], $result);
        $this->assertSame(['fileIdPhoto:100' => [['_' => 'fileSourceUserProfilePhoto', 'user_id' => 9, 'max_id' => 100]]], self::sourcesOf($commits));

        // A result of another constructor aborts.
        $commits = self::method('photos.updateProfilePhoto', [], ['_' => 'photos.photo', 'photo' => ['_' => 'photoEmpty', 'id' => 100], 'users' => []]);
        $this->assertSame([], $commits);
    }

    public function testAbortIfEmpty(): void
    {
        // botInfo.user_id?abort_if_empty: a bot info without user_id is not a fileSourceUserFull.
        $commits = self::method('channels.getFullChannel', ['channel' => -1000000001234], ['_' => 'messages.chatFull', 'full_chat' => [
            '_' => 'channelFull',
            'id' => -1000000001234,
            'chat_photo' => ['_' => 'photoEmpty', 'id' => 0],
            'bot_info' => [
                ['_' => 'botInfo', 'user_id' => 5, 'description_photo' => self::photo(100)],
                ['_' => 'botInfo', 'description_photo' => self::photo(101)],
            ],
        ], 'chats' => [], 'users' => []]);
        $this->assertSame([
            'fileIdPhoto:100' => [['_' => 'fileSourceChannelFull', 'channel' => 1234], ['_' => 'fileSourceUserFull', 'id' => 5]],
            'fileIdPhoto:101' => [['_' => 'fileSourceChannelFull', 'channel' => 1234]],
        ], self::sourcesOf($commits));

        // userFull.saved_music?abort_if_empty.document.id
        $full = ['_' => 'userFull', 'id' => 9, 'saved_music' => self::document(200)];
        $commits = self::method('users.getFullUser', ['id' => 9], ['_' => 'users.userFull', 'full_user' => $full, 'chats' => [], 'users' => []]);
        $this->assertSame(['fileIdDocument:200' => [
            ['_' => 'fileSourceUserFull', 'id' => 9],
            ['_' => 'fileSourceSavedMusic', 'user_id' => 9, 'id' => 200, 'access_hash' => 22],
        ]], self::sourcesOf($commits));
        $full = ['_' => 'userFull', 'id' => 9, 'profile_photo' => self::photo(100)];
        $commits = self::method('users.getFullUser', ['id' => 9], ['_' => 'users.userFull', 'full_user' => $full, 'chats' => [], 'users' => []]);
        $this->assertSame(['fileIdPhoto:100' => [['_' => 'fileSourceUserFull', 'id' => 9]]], self::sourcesOf($commits));
    }

    public function testTheIdOfAChatIsTheOneOfTheApi(): void
    {
        $commits = self::method('messages.getFullChat', ['chat_id' => 123], ['_' => 'messages.chatFull', 'full_chat' => [
            '_' => 'chatFull',
            'id' => -123,
            'chat_photo' => self::photo(100),
        ], 'chats' => [], 'users' => []]);
        $this->assertSame(['fileIdPhoto:100' => [['_' => 'fileSourceChatFull', 'chat_id' => 123]]], self::sourcesOf($commits));
        $this->assertSame(
            ['messages.getFullChat', ['chat_id' => 123]],
            FileRefMap::get()->buildRefreshCall($commits[0]['sources'][0], self::env()),
        );
    }

    public static function provideChannels(): iterable
    {
        yield 'channel' => [['_' => 'inputChannel', 'channel_id' => 1234, 'access_hash' => 1], 1234];
        yield 'from message' => [['_' => 'inputChannelFromMessage', 'peer' => ['_' => 'inputPeerSelf'], 'msg_id' => 1, 'channel_id' => 1234], 1234];
        yield 'deserialized' => [['_' => 'inputChannel', 'channel_id' => -1000000001234, 'access_hash' => 1], 1234];
        yield 'bot API ID' => [-1000000001234, 1234];
        yield 'empty' => [['_' => 'inputChannelEmpty'], null];
        yield 'user' => [9, null];
    }

    /**
     * @dataProvider provideChannels
     */
    public function testAdminLog(mixed $channel, ?int $expected): void
    {
        $commits = self::method('channels.getAdminLog', ['channel' => $channel, 'q' => '', 'max_id' => 0, 'min_id' => 0, 'limit' => 1], ['_' => 'channels.adminLogResults', 'events' => [[
            '_' => 'channelAdminLogEvent',
            'id' => 77,
            'date' => 0,
            'user_id' => 9,
            'action' => ['_' => 'channelAdminLogEventActionChangePhoto', 'prev_photo' => self::photo(100), 'new_photo' => self::photo(101)],
        ]], 'chats' => [], 'users' => []]);
        $sources = $expected === null ? [] : [['_' => 'fileSourceAdminLog', 'channel' => $expected, 'max_id' => 77]];
        $this->assertSame(['fileIdPhoto:100' => $sources, 'fileIdPhoto:101' => $sources], self::sourcesOf($commits));
    }

    public function testStickerSet(): void
    {
        $commits = self::method('messages.getStickerSet', ['stickerset' => ['_' => 'inputStickerSetShortName', 'short_name' => 'a'], 'hash' => 0], [
            '_' => 'messages.stickerSet',
            'set' => ['_' => 'stickerSet', 'id' => 3, 'access_hash' => 4, 'title' => '', 'short_name' => 'a', 'count' => 1, 'hash' => 0],
            'packs' => [],
            'keywords' => [],
            'documents' => [self::document(200)],
        ]);
        $this->assertSame(['fileIdDocument:200' => [
            ['_' => 'fileSourceStickerSet', 'stickerset' => ['_' => 'inputStickerSetID', 'id' => 3, 'access_hash' => 4]],
        ]], self::sourcesOf($commits));
    }

    public function testStoriesOfMessages(): void
    {
        // messageMediaStory.story?abort_if_empty.storyItem.id: a path through a flag and a constructor.
        $story = ['_' => 'storyItem', 'id' => 3, 'date' => 0, 'expire_date' => 0, 'from_id' => 42, 'media' => ['_' => 'messageMediaPhoto', 'photo' => self::photo(100)]];
        $commits = self::update(['_' => 'updateNewMessage', 'message' => self::message(10, 9, ['_' => 'messageMediaStory', 'peer' => 42, 'id' => 3, 'story' => $story])]);
        $this->assertSame(['fileIdPhoto:100' => [
            ['_' => 'fileSourceMessage', 'peer' => 9, 'id' => 10],
            // Also pushed by storyItem (from_id?abort_if_empty): the same source is committed once.
            ['_' => 'fileSourceStory', 'id' => 3, 'peer' => 42],
        ]], self::sourcesOf($commits));
        // Without from_id, storyItem doesn't push it.
        unset($story['from_id']);
        $commits = self::update(['_' => 'updateNewMessage', 'message' => self::message(10, 9, ['_' => 'messageMediaStory', 'peer' => 43, 'id' => 3, 'story' => $story])]);
        $this->assertSame(['fileIdPhoto:100' => [
            ['_' => 'fileSourceMessage', 'peer' => 9, 'id' => 10],
            ['_' => 'fileSourceStory', 'id' => 3, 'peer' => 43],
        ]], self::sourcesOf($commits));
    }

    public function testVectorResultsAndSourcelessMethods(): void
    {
        // A method returning a vector, whose traverser has no source: the file references are stored anyway.
        $commits = self::method('messages.getCustomEmojiDocuments', ['document_id' => [200]], [self::document(200), self::document(201)]);
        $this->assertSame(['fileIdDocument:200' => [], 'fileIdDocument:201' => []], self::sourcesOf($commits));
        $this->assertSame('ref201', $commits[1]['reference']);

        // Methods and constructors without traversers are not traversed.
        $this->assertSame([], self::method('messages.sendMessage', [], ['_' => 'messages.messages', 'messages' => [self::message(5, 42, ['_' => 'messageMediaPhoto', 'photo' => self::photo(100)])]]));
        $this->assertSame([], self::update(['_' => 'updateUnknown', 'message' => self::message(5, 42, ['_' => 'messageMediaPhoto', 'photo' => self::photo(100)])]));
    }

    /**
     * A map with one method, whose sources use the given extractors on its argument `x`.
     */
    private static function syntheticMap(array $sources, array $extraIncoming = []): FileRefMap
    {
        return FileRefMap::fromArray([
            '_' => 'fileReferenceMap',
            'layer' => 1,
            'db_schema' => '',
            'db_schema_json' => '',
            'traversers_incoming' => [
                ['_' => 'traverseMethodResult', 'name' => 'test.method', 'push_sources' => $sources, 'is_needed_parent' => true],
                ['_' => 'traverseCommitSourceLocation', 'type' => 'Document', 'predicate' => 'document', 'push_sources' => [], 'stored_constructor' => 'fileIdDocument'],
                ...$extraIncoming,
            ],
            'traversers_outgoing' => [],
            'refresh_actions' => [],
            'skipped_incoming_sources' => [],
        ]);
    }
    private static function part(string $constructor, string $param, string $paramType, array $flag = ['_' => 'paramNotFlag']): array
    {
        return ['_' => 'pathPart', 'type' => 'X', 'constructor' => $constructor, 'param' => $param, 'param_type' => $paramType, 'flag' => $flag];
    }
    private static function source(array $storedParams, array $extra = []): array
    {
        return [
            '_' => 'source',
            'predicate' => ['_' => 'predicate', 'predicate' => 'test.method', 'is_constructor' => false],
            'stored_constructor' => 'fileSourceTest',
            'stored_params' => $storedParams,
            'skipped_flags' => [],
            'needs_not_parents' => [],
            ...$extra,
        ];
    }

    public static function provideExtractors(): iterable
    {
        $channel = ['_' => 'channel', 'id' => -1000000001234];
        yield 'channel' => ['extractChannelIdFromChannelAndStore', 'Channel', $channel, 1234];
        yield 'not a channel' => ['extractChannelIdFromChannelAndStore', 'Channel', ['_' => 'channelEmpty'], null];
        yield 'user' => ['extractUserIdFromUserAndStore', 'User', ['_' => 'user', 'id' => 9], 9];
        yield 'not a user' => ['extractUserIdFromUserAndStore', 'User', ['_' => 'userEmpty', 'id' => 9], null];
        yield 'peer' => ['extractPeerIdFromPeerAndStore', 'Peer', -1000000001234, -1000000001234];
        yield 'raw peer' => ['extractPeerIdFromPeerAndStore', 'Peer', ['_' => 'peerUser', 'user_id' => 9], 9];
        yield 'stickerset' => ['extractInputStickerSetFromStickerSetAndStore', 'StickerSet', ['_' => 'stickerSet', 'id' => 3, 'access_hash' => 4], ['_' => 'inputStickerSetID', 'id' => 3, 'access_hash' => 4]];
        yield 'no sticker attribute' => ['extractInputStickerSetFromDocumentAttributesAndStore', 'DocumentAttribute', [['_' => 'documentAttributeFilename', 'file_name' => 'a']], null];
        yield 'bytes' => ['extractAndStore', 'bytes', new Bytes('abc'), 'abc'];
    }

    /**
     * @dataProvider provideExtractors
     */
    public function testExtractors(string $extractor, string $type, mixed $value, mixed $expected): void
    {
        $map = self::syntheticMap([self::source([
            ['_' => $extractor, 'from' => ['_' => 'path', 'parts' => [self::part('test.method', 'x', $type)]], 'to' => 'y'],
        ])]);
        $commits = FileRefTraversal::traverseMethodResult($map, self::env(), 'test.method', ['x' => $value], self::document(200));
        $this->assertSame($expected === null ? [] : [['_' => 'fileSourceTest', 'y' => $expected]], $commits[0]['sources']);
    }

    public function testPathsCheckTheirConstructors(): void
    {
        // x.storyItem.id: aborts on another constructor of the type.
        $map = self::syntheticMap([self::source([
            ['_' => 'extractAndStore', 'from' => ['_' => 'path', 'parts' => [self::part('test.method', 'x', 'StoryItem'), self::part('storyItem', 'id', 'int')]], 'to' => 'y'],
        ])]);
        $commits = FileRefTraversal::traverseMethodResult($map, self::env(), 'test.method', ['x' => ['_' => 'storyItem', 'id' => 3]], self::document(200));
        $this->assertSame([['_' => 'fileSourceTest', 'y' => 3]], $commits[0]['sources']);
        $commits = FileRefTraversal::traverseMethodResult($map, self::env(), 'test.method', ['x' => ['_' => 'storyItemSkipped', 'id' => 3]], self::document(200));
        $this->assertSame([], $commits[0]['sources']);
    }

    public function testFlagsOfPaths(): void
    {
        $map = self::syntheticMap([self::source([
            // paramIsFlagFallback with a literal
            ['_' => 'extractAndStore', 'from' => ['_' => 'path', 'parts' => [self::part('test.method', 'a', 'int', ['_' => 'paramIsFlagFallback', 'fallback' => ['_' => 'typedOp', 'type' => 'int', 'op' => ['_' => 'intLiteralOp', 'value' => 5]]])]], 'to' => 'a'],
            // paramIsFlagPassthrough of a true flag
            ['_' => 'extractAndStore', 'from' => ['_' => 'path', 'parts' => [self::part('test.method', 'b', 'true', ['_' => 'paramIsFlagPassthrough'])]], 'to' => 'b'],
            // An omitted string argument is the empty string
            ['_' => 'extractAndStore', 'from' => ['_' => 'path', 'parts' => [self::part('test.method', 'c', 'string')]], 'to' => 'c'],
        ], ['skipped_flags' => ['d']])]);
        $commits = FileRefTraversal::traverseMethodResult($map, self::env(), 'test.method', [], self::document(200));
        $this->assertSame([['_' => 'fileSourceTest', 'a' => 5, 'c' => '']], $commits[0]['sources']);
        $commits = FileRefTraversal::traverseMethodResult($map, self::env(), 'test.method', ['a' => 1, 'b' => true, 'c' => 'x'], self::document(200));
        $this->assertSame([['_' => 'fileSourceTest', 'a' => 1, 'b' => true, 'c' => 'x']], $commits[0]['sources']);
        $commits = FileRefTraversal::traverseMethodResult($map, self::env(), 'test.method', ['b' => false], self::document(200));
        $this->assertSame([['_' => 'fileSourceTest', 'a' => 5, 'c' => '']], $commits[0]['sources']);

        // paramIsFlagAbortIfEmpty
        $map = self::syntheticMap([self::source([
            ['_' => 'extractAndStore', 'from' => ['_' => 'path', 'parts' => [self::part('test.method', 'a', 'int', ['_' => 'paramIsFlagAbortIfEmpty'])]], 'to' => 'a'],
        ])]);
        $this->assertSame([], FileRefTraversal::traverseMethodResult($map, self::env(), 'test.method', [], self::document(200))[0]['sources']);
        $this->assertSame([['_' => 'fileSourceTest', 'a' => 0]], FileRefTraversal::traverseMethodResult($map, self::env(), 'test.method', ['a' => 0], self::document(200))[0]['sources']);
    }

    public function testParents(): void
    {
        // A source needing a parent constructor, a source excluded by it, a parent popped after its branch.
        $parent = static fn (string $name): array => ['_' => 'predicate', 'predicate' => $name, 'is_constructor' => true];
        $map = self::syntheticMap([], [
            ['_' => 'traverseIncomingConstructor', 'type' => 'X', 'predicate' => 'wrapper', 'params' => [
                ['_' => 'traverseParam', 'name' => 'inner', 'is_flag' => false, 'is_vector' => true, 'type' => 'X'],
                ['_' => 'traverseParam', 'name' => 'doc', 'is_flag' => false, 'is_vector' => false, 'type' => 'Document'],
            ], 'push_sources' => [], 'is_needed_parent' => false],
            ['_' => 'traverseIncomingConstructor', 'type' => 'X', 'predicate' => 'parent', 'params' => [
                ['_' => 'traverseParam', 'name' => 'child', 'is_flag' => false, 'is_vector' => false, 'type' => 'X'],
            ], 'push_sources' => [], 'is_needed_parent' => true],
            ['_' => 'traverseIncomingConstructor', 'type' => 'X', 'predicate' => 'child', 'params' => [
                ['_' => 'traverseParam', 'name' => 'doc', 'is_flag' => false, 'is_vector' => false, 'type' => 'Document'],
            ], 'push_sources' => [
                [...self::source([['_' => 'extractAndStore', 'from' => ['_' => 'pathParent', 'parts' => [self::part('parent', 'v', 'int')]], 'to' => 'v']]), 'stored_constructor' => 'fileSourceWithParent', 'needs_parent' => $parent('parent')],
                [...self::source([['_' => 'extractAndStore', 'from' => ['_' => 'path', 'parts' => [self::part('child', 'v', 'int')]], 'to' => 'v']]), 'stored_constructor' => 'fileSourceWithoutParent', 'needs_not_parents' => [$parent('parent')]],
            ], 'is_needed_parent' => false],
        ]);
        $commits = FileRefTraversal::traverseUpdate($map, self::env(), ['_' => 'wrapper', 'inner' => [
            ['_' => 'parent', 'v' => 1, 'child' => ['_' => 'child', 'v' => 2, 'doc' => self::document(200)]],
            ['_' => 'child', 'v' => 3, 'doc' => self::document(201)],
        ], 'doc' => self::document(202)]);
        $this->assertSame([
            'fileIdDocument:200' => [['_' => 'fileSourceWithParent', 'v' => 1]],
            'fileIdDocument:201' => [['_' => 'fileSourceWithoutParent', 'v' => 3]],
            'fileIdDocument:202' => [],
        ], self::sourcesOf($commits));
    }

    private static function inputPhoto(int $id): array
    {
        return ['_' => 'inputPhoto', 'id' => $id, 'access_hash' => 11, 'file_reference' => "old$id"];
    }
    private static function inputDocument(int $id): array
    {
        return ['_' => 'inputDocument', 'id' => $id, 'access_hash' => 22, 'file_reference' => "old$id"];
    }
    private static function locations(string $method, array $args, ?callable $normalize = null): array
    {
        [$args, $locations] = FileRefMap::get()->findLocations($method, $args, $normalize ?? static fn (string $type, mixed $value): mixed => $value);
        return array_map(static fn (array $l): array => [FileRefMap::getFileIdKey($l['fileId']), $l['group'], $l['reference']], $locations);
    }

    public function testTheLocationsOfMultiMedia(): void
    {
        $this->assertSame([
            ['fileIdPhoto:1', 0, 'old1'],
            ['fileIdDocument:2', 1, 'old2'],
            ['fileIdPhoto:3', 1, 'old3'],
            ['fileIdDocument:4', 2, 'old4'],
        ], self::locations('messages.sendMultiMedia', ['peer' => 1, 'multi_media' => [
            ['_' => 'inputSingleMedia', 'media' => ['_' => 'inputMediaPhoto', 'id' => self::inputPhoto(1)], 'message' => ''],
            ['_' => 'inputSingleMedia', 'media' => ['_' => 'inputMediaDocument', 'id' => self::inputDocument(2), 'video_cover' => self::inputPhoto(3)], 'message' => ''],
            ['_' => 'inputSingleMedia', 'media' => ['_' => 'inputMediaDocument', 'id' => self::inputDocument(4)], 'message' => ''],
        ]]));
    }

    public function testTheLocationsOfPaidMedia(): void
    {
        $this->assertSame([
            ['fileIdPhoto:5', 0, 'old5'],
            ['fileIdDocument:6', 1, 'old6'],
        ], self::locations('messages.sendMedia', ['peer' => 1, 'message' => '', 'media' => [
            '_' => 'inputMediaPaidMedia',
            'stars_amount' => 1,
            'extended_media' => [
                ['_' => 'inputMediaPhoto', 'id' => self::inputPhoto(5)],
                ['_' => 'inputMediaDocument', 'id' => self::inputDocument(6)],
            ],
        ]]));
    }

    public function testLocationsAreNormalizedAndSwapped(): void
    {
        // A MessageMedia passed as an InputMedia is converted as the serializer would.
        $args = ['peer' => 1, 'message' => '', 'media' => ['_' => 'messageMediaPhoto', 'photo' => self::photo(7)]];
        $normalize = static function (string $type, mixed $value): mixed {
            if ($type === 'InputMedia' && ($value['_'] ?? null) === 'messageMediaPhoto') {
                return ['_' => 'inputMediaPhoto', 'id' => ['_' => 'inputPhoto', 'id' => $value['photo']['id'], 'access_hash' => 11, 'file_reference' => (string) $value['photo']['file_reference']]];
            }
            return $value;
        };
        [$args, $locations] = FileRefMap::get()->findLocations('messages.sendMedia', $args, $normalize);
        $this->assertSame([['fileId' => ['_' => 'fileIdPhoto', 'id' => 7], 'group' => null, 'path' => ['media', 'id'], 'reference' => 'ref7']], $locations);
        $args = FileRefMap::swapReferences($args, $locations, ['fileIdPhoto:7' => 'new7']);
        $this->assertSame(['_' => 'inputPhoto', 'id' => 7, 'access_hash' => 11, 'file_reference' => 'new7'], $args['media']['id']);

        // Downloads.
        $location = ['_' => 'inputDocumentFileLocation', 'id' => 8, 'access_hash' => 22, 'file_reference' => new Bytes('old8'), 'thumb_size' => ''];
        $this->assertSame([['fileIdDocument:8', null, 'old8']], self::locations('upload.getFile', ['location' => $location, 'offset' => 0, 'limit' => 1]));
        // Methods without media.
        $this->assertSame([], self::locations('messages.sendMessage', ['peer' => 1, 'message' => 'a']));
    }

    public static function provideRefreshActions(): iterable
    {
        $user = ['_' => 'inputUser', 'user_id' => 9, 'access_hash' => 1];
        $channel = ['_' => 'inputChannel', 'channel_id' => 1234, 'access_hash' => 1];
        yield 'message (user)' => [['_' => 'fileSourceMessage', 'peer' => 9, 'id' => 5], ['messages.getMessages', ['id' => [['_' => 'inputMessageID', 'id' => 5]]]]];
        yield 'message (channel)' => [['_' => 'fileSourceMessage', 'peer' => -1000000001234, 'id' => 5], ['channels.getMessages', ['channel' => $channel, 'id' => [['_' => 'inputMessageID', 'id' => 5]]]]];
        yield 'message (quick reply)' => [['_' => 'fileSourceMessage', 'quick_reply_shortcut_id' => 7, 'peer' => 9, 'id' => 5], ['messages.getQuickReplyMessages', ['shortcut_id' => 7, 'id' => [5], 'hash' => 0]]];
        yield 'scheduled message' => [['_' => 'fileSourceScheduledMessage', 'peer' => 9, 'id' => 5], ['messages.getScheduledMessages', ['peer' => ['_' => 'inputPeerUser', 'user_id' => 9, 'access_hash' => 1], 'id' => [5]]]];
        yield 'story' => [['_' => 'fileSourceStory', 'id' => 3, 'peer' => -1000000001234], ['stories.getStoriesByID', ['id' => [3], 'peer' => ['_' => 'inputPeerChannel', 'channel_id' => 1234, 'access_hash' => 1]]]];
        yield 'web page' => [['_' => 'fileSourceWebPage', 'url' => 'https://a'], ['messages.getWebPage', ['url' => 'https://a', 'hash' => 0]]];
        yield 'bot app' => [['_' => 'fileSourceBotApp', 'id' => 3, 'access_hash' => 4], ['messages.getBotApp', ['app' => ['_' => 'inputBotAppID', 'id' => 3, 'access_hash' => 4], 'hash' => 0]]];
        yield 'user full' => [['_' => 'fileSourceUserFull', 'id' => 9], ['users.getFullUser', ['id' => $user]]];
        yield 'admin log' => [['_' => 'fileSourceAdminLog', 'channel' => 1234, 'max_id' => 77], ['channels.getAdminLog', ['channel' => $channel, 'max_id' => 77, 'min_id' => 77, 'limit' => 1, 'q' => '']]];
        yield 'story album' => [['_' => 'fileSourceStoryAlbum', 'peer' => 9], ['stories.getAlbums', ['peer' => ['_' => 'inputPeerUser', 'user_id' => 9, 'access_hash' => 1], 'hash' => 0]]];
        yield 'bot preview media' => [['_' => 'fileSourceBotPreviewMedia', 'bot' => 9], ['bots.getPreviewMedias', ['bot' => $user]]];
        yield 'bot preview info' => [['_' => 'fileSourceBotPreviewInfo', 'bot' => 9, 'lang_code' => 'en'], ['bots.getPreviewInfo', ['bot' => $user, 'lang_code' => 'en']]];
        yield 'paid media' => [['_' => 'fileSourcePaidMedia', 'id' => 5, 'peer' => 9], ['messages.getExtendedMedia', ['id' => [5], 'peer' => ['_' => 'inputPeerUser', 'user_id' => 9, 'access_hash' => 1]]]];
        yield 'saved music' => [['_' => 'fileSourceSavedMusic', 'user_id' => 9, 'id' => 200, 'access_hash' => 22], ['users.getSavedMusicByID', ['id' => $user, 'documents' => [['_' => 'inputDocument', 'id' => 200, 'access_hash' => 22, 'file_reference' => '']]]]];
        yield 'chat full' => [['_' => 'fileSourceChatFull', 'chat_id' => 123], ['messages.getFullChat', ['chat_id' => 123]]];
        yield 'channel full' => [['_' => 'fileSourceChannelFull', 'channel' => 1234], ['channels.getFullChannel', ['channel' => $channel]]];
        yield 'community full' => [['_' => 'fileSourceCommunityFull', 'channel' => 1234], ['channels.getFullChannel', ['channel' => $channel]]];
        yield 'premium promo' => [['_' => 'fileSourcePremiumPromo'], ['help.getPremiumPromo', []]];
        yield 'stars transaction' => [['_' => 'fileSourceStarsTransaction', 'peer' => 9, 'id' => 'tx', 'refund' => true], ['payments.getStarsTransactionsByID', ['peer' => ['_' => 'inputPeerUser', 'user_id' => 9, 'access_hash' => 1], 'id' => [['_' => 'inputStarsTransaction', 'id' => 'tx', 'refund' => true]]]]];
        yield 'ton transaction' => [['_' => 'fileSourceStarsTransaction', 'peer' => 9, 'id' => 'tx', 'ton' => true], ['payments.getStarsTransactionsByID', ['peer' => ['_' => 'inputPeerUser', 'user_id' => 9, 'access_hash' => 1], 'ton' => true, 'id' => [['_' => 'inputStarsTransaction', 'id' => 'tx']]]]];
        yield 'attach menu bot' => [['_' => 'fileSourceAttachMenuBot', 'bot' => 9], ['messages.getAttachMenuBot', ['bot' => $user]]];
        yield 'theme' => [['_' => 'fileSourceTheme', 'id' => 3, 'access_hash' => 4], ['account.getTheme', ['theme' => ['_' => 'inputTheme', 'id' => 3, 'access_hash' => 4], 'format' => 'test-format']]];
        yield 'wallpaper' => [['_' => 'fileSourceWallPaper', 'id' => 3, 'access_hash' => 4], ['account.getWallPaper', ['wallpaper' => ['_' => 'inputWallPaper', 'id' => 3, 'access_hash' => 4]]]];
        yield 'sticker set' => [['_' => 'fileSourceStickerSet', 'stickerset' => ['_' => 'inputStickerSetID', 'id' => 3, 'access_hash' => 4]], ['messages.getStickerSet', ['stickerset' => ['_' => 'inputStickerSetID', 'id' => 3, 'access_hash' => 4], 'hash' => 0]]];
        yield 'saved gifs' => [['_' => 'fileSourceSavedGifs'], ['messages.getSavedGifs', ['hash' => 0]]];
        yield 'saved ringtones' => [['_' => 'fileSourceSavedRingtones'], ['account.getSavedRingtones', ['hash' => 0]]];
        yield 'available effects' => [['_' => 'fileSourceAvailableEffects'], ['messages.getAvailableEffects', ['hash' => 0]]];
        yield 'available reactions' => [['_' => 'fileSourceAvailableReactions'], ['messages.getAvailableReactions', ['hash' => 0]]];
        yield 'user profile photo' => [['_' => 'fileSourceUserProfilePhoto', 'user_id' => 9, 'max_id' => 100], ['photos.getUserPhotos', ['user_id' => $user, 'offset' => -1, 'max_id' => 100, 'limit' => 1]]];
        yield 'document by hash' => [['_' => 'fileSourceDocumentByHash', 'sha256' => 'h', 'size' => 1, 'mime_type' => 'a/b'], ['messages.getDocumentByHash', ['sha256' => 'h', 'size' => 1, 'mime_type' => 'a/b']]];
        yield 'welcome messages' => [['_' => 'fileSourceWelcomeMessages', 'peer' => 9], ['ephemeral.getWelcomeMessages', ['peer' => ['_' => 'inputPeerUser', 'user_id' => 9, 'access_hash' => 1], 'hash' => 0]]];
        yield 'unknown' => [['_' => 'fileSourceUnknown'], null];
    }

    /**
     * @dataProvider provideRefreshActions
     */
    public function testRefreshActions(array $source, ?array $expected): void
    {
        $this->assertSame($expected, FileRefMap::get()->buildRefreshCall($source, self::env()));
    }
}

/**
 * Peers as MadelineProto's peer database would return them, all with access hash 1.
 *
 * @internal
 */
final class TestFileRefEnvironment implements FileRefEnvironment
{
    public int $suspensions = 0;

    public function __construct(private readonly int $self, private readonly bool $suspend = false)
    {
    }
    public function getSelfId(): ?int
    {
        return $this->self;
    }
    public function getPeerId(mixed $peer): ?int
    {
        if ($this->suspend) {
            $this->suspensions++;
            delay(0.001);
        }
        if (\is_int($peer)) {
            return $peer;
        }
        if (!\is_array($peer)) {
            return null;
        }
        return match ($peer['_']) {
            'inputPeerUser', 'inputUser', 'peerUser' => $peer['user_id'],
            'inputPeerChannel', 'inputChannel', 'peerChannel' => FileRefMap::channelIdToBotApi($peer['channel_id']),
            default => null,
        };
    }
    public function getInputPeerById(int $id): array
    {
        if ($id > 0) {
            return ['_' => 'inputPeerUser', 'user_id' => $id, 'access_hash' => 1];
        }
        if ($id > -1000000000000) {
            return ['_' => 'inputPeerChat', 'chat_id' => -$id];
        }
        return ['_' => 'inputPeerChannel', 'channel_id' => FileRefMap::channelIdToMTProto($id), 'access_hash' => 1];
    }
    public function getInputUserById(int $id): array
    {
        return ['_' => 'inputUser', 'user_id' => $id, 'access_hash' => 1];
    }
    public function getInputChannelById(int $id): array
    {
        return ['_' => 'inputChannel', 'channel_id' => FileRefMap::channelIdToMTProto($id), 'access_hash' => 1];
    }
    public function getThemeFormat(): string
    {
        return 'test-format';
    }
}
