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

use danog\AsyncOrm\Driver\MemoryArray;
use danog\MadelineProto\API;
use danog\MadelineProto\APIWrapper;
use danog\MadelineProto\Connection;
use danog\MadelineProto\DataCenter;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\LoginState;
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;
use danog\MadelineProto\MTProto\SpecialMethodType;
use danog\MadelineProto\MTProtoTools\MinDatabase;
use danog\MadelineProto\MTProtoTools\PeerDatabase;
use danog\MadelineProto\MTProtoTools\ReferenceDatabase;
use danog\MadelineProto\Reactive\Publisher;
use danog\MadelineProto\RPCError\FileReferenceExpiredError;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use danog\MadelineProto\TL\TL;
use danog\MadelineProto\TL\Types\Bytes;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionObject;
use ReflectionProperty;

use function Amp\async;
use function Amp\delay;
use function Amp\Future\await;

/**
 * The file reference database of a client: storing the results of method calls, refreshing file references with
 * the refresh actions of their sources, swapping them into the failed calls.
 *
 * The client is a bare instance whose requests are answered by {@see self::answer()}, which stores the result as
 * the response handler would: the classes on the way are final, so they're mocked in a separate process, with
 * BypassFinals enabled before they're loaded.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @internal
 */
final class ReferenceDatabaseTest extends TestCase
{
    /** @var list<array{string, array}> The methods called, with their arguments. */
    private array $calls = [];
    /** @var array<string, \Closure(array): mixed> Answers, by method. */
    private array $answers = [];
    private MTProto $API;
    private ReferenceDatabase $db;

    protected function setUp(): void
    {
        BypassFinals::enable();
    }

    private function client(?MemoryArray $references = null): MTProto
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $API->logger = new Logger((new LoggerSettings)->setType(Logger::ECHO_LOGGER)->setLevel(Logger::FATAL_ERROR));
        $API->wrapper = new APIWrapper(new SessionPaths(sys_get_temp_dir().'/madeline-reference-database'));
        (new ReflectionProperty($API, 'settings'))->setValue($API, $settings = new Settings);
        $API->loginState = new Publisher(new LoginState(API::LOGGED_IN, 2));
        $API->authorization = ['user' => ['_' => 'user', 'id' => 1, 'self' => true, 'bot' => false]];
        $connection = $this->createMock(Connection::class);
        $connection->method('methodCallAsyncRead')->willReturnCallback(function (string $method, array $args): mixed {
            $this->calls[] = [$method, $args];
            if (!isset($this->answers[$method])) {
                return ['_' => 'messages.chats', 'chats' => []];
            }
            $result = ($this->answers[$method])($args);
            delay(0.001);
            // As the response handler does.
            $this->API->referenceDatabase->onMethodResult($method, $args, $result);
            return $result;
        });
        $datacenter = $this->createMock(DataCenter::class);
        $datacenter->currentDatacenter = 2;
        $datacenter->method('waitGetConnection')->willReturn($connection);
        $API->datacenter = $datacenter;

        $API->peerDatabase = new PeerDatabase($API);
        foreach (['db', 'usernames', 'fullDb'] as $property) {
            (new ReflectionProperty($API->peerDatabase, $property))->setValue($API->peerDatabase, new MemoryArray([]));
        }
        $API->minDatabase = new MinDatabase($API);
        (new ReflectionProperty($API->minDatabase, 'db'))->setValue($API->minDatabase, new MemoryArray([]));
        $API->referenceDatabase = $this->db = new ReferenceDatabase($API);
        (new ReflectionProperty($this->db, 'db'))->setValue($this->db, $references ?? new MemoryArray([]));
        (new ReflectionProperty($this->db, 'sources'))->setValue($this->db, new MemoryArray([]));

        $TL = new TL($API);
        // The order of the callbacks after a wakeup.
        $TL->init($settings->getSchema(), [$API, $API->peerDatabase, $this->db, $API->minDatabase]);
        (new ReflectionProperty($API, 'TL'))->setValue($API, $TL);
        return $this->API = $API;
    }

    private static function document(int $id, string $reference): array
    {
        return ['_' => 'document', 'id' => $id, 'access_hash' => 22, 'file_reference' => new Bytes($reference), 'date' => 0, 'mime_type' => 'video/mp4', 'size' => 1, 'dc_id' => 2, 'attributes' => []];
    }
    private static function photo(int $id, string $reference): array
    {
        return ['_' => 'photo', 'id' => $id, 'access_hash' => 11, 'file_reference' => new Bytes($reference), 'date' => 0, 'sizes' => [], 'dc_id' => 2];
    }
    private static function stickerSet(array $documents): array
    {
        return ['_' => 'messages.stickerSet', 'set' => ['_' => 'stickerSet', 'id' => 3, 'access_hash' => 4, 'title' => '', 'short_name' => 'a', 'count' => 1, 'hash' => 0], 'packs' => [], 'keywords' => [], 'documents' => $documents];
    }
    private static function savedGifs(array $documents): array
    {
        return ['_' => 'messages.savedGifs', 'hash' => 0, 'gifs' => $documents];
    }
    private static function premiumPromo(array $documents): array
    {
        return ['_' => 'help.premiumPromo', 'status_text' => '', 'status_entities' => [], 'video_sections' => [], 'videos' => $documents, 'period_options' => [], 'users' => []];
    }
    /** @return list<string> */
    private function methods(): array
    {
        return array_column($this->calls, 0);
    }
    private function sources(string $key): array
    {
        return (fn () => $this->getSources($key))->call($this->db);
    }

    /**
     * Document 200 seen in a sticker set, then in the saved GIFs, then in the premium promo.
     */
    private function seenInThreePlaces(): void
    {
        $this->db->onMethodResult('messages.getStickerSet', ['stickerset' => ['_' => 'inputStickerSetID', 'id' => 3, 'access_hash' => 4]], self::stickerSet([self::document(200, 'old')]));
        $this->db->onMethodResult('messages.getSavedGifs', [], self::savedGifs([self::document(200, 'old')]));
        $this->db->onMethodResult('help.getPremiumPromo', [], self::premiumPromo([self::document(200, 'old')]));
        $this->assertSame(['fileSourceStickerSet', 'fileSourceSavedGifs', 'fileSourcePremiumPromo'], array_column($this->sources('fileIdDocument:200'), '_'));
        $this->assertSame('old', $this->db->getReference(ReferenceDatabase::DOCUMENT, self::document(200, 'other')));
    }

    public function testTheRefreshStopsAtTheFirstChangedReference(): void
    {
        $this->client();
        $this->seenInThreePlaces();
        $this->answers = [
            'help.getPremiumPromo' => static fn () => self::premiumPromo([self::document(200, 'old')]),
            'messages.getSavedGifs' => static fn () => self::savedGifs([self::document(200, 'new')]),
            'messages.getStickerSet' => static fn () => self::stickerSet([self::document(200, 'newer')]),
        ];
        // The most recently seen source first; the premium promo still has the old file reference.
        $this->assertSame('new', $this->db->refreshFileId(['_' => 'fileIdDocument', 'id' => 200], 'old'));
        $this->assertSame(['help.getPremiumPromo', 'messages.getSavedGifs'], $this->methods());
        $this->assertSame(SpecialMethodType::FILEREF_RELATED, $this->calls[0][1]['specialMethodType']);
        $this->assertSame(['hash' => 0], array_diff_key($this->calls[1][1], ['specialMethodType' => true]));
        $this->assertSame('new', $this->db->getReference(ReferenceDatabase::DOCUMENT, self::document(200, 'old')));
    }

    public function testTheRefreshFailsWhenTheSourcesAreExhausted(): void
    {
        $this->client();
        $this->seenInThreePlaces();
        $this->answers = [
            // Built in its scope: the constructor is protected, and make() would report the error over the network.
            'help.getPremiumPromo' => static fn () => throw \Closure::bind(static fn () => new RPCErrorException('PREMIUM_ACCOUNT_REQUIRED', 'Telegram Premium is required', 400, 'help.getPremiumPromo'), null, RPCErrorException::class)(),
            'messages.getSavedGifs' => static fn () => self::savedGifs([]),
            'messages.getStickerSet' => static fn () => self::stickerSet([self::document(200, 'old')]),
        ];
        $args = ['location' => ['_' => 'inputDocumentFileLocation', 'id' => 200, 'access_hash' => 22, 'file_reference' => 'old', 'thumb_size' => ''], 'offset' => 0, 'limit' => 1024];
        $this->assertNull($this->db->refreshMethodCall('upload.getFile', $args, 'FILE_REFERENCE_EXPIRED'));
        $this->assertSame(['help.getPremiumPromo', 'messages.getSavedGifs', 'messages.getStickerSet'], $this->methods());

        // Unknown files and calls without media can't be refreshed.
        $this->calls = [];
        $args['location']['id'] = 201;
        $this->assertNull($this->db->refreshMethodCall('upload.getFile', $args, 'FILE_REFERENCE_EXPIRED'));
        $this->assertNull($this->db->refreshMethodCall('messages.sendMessage', ['peer' => 1, 'message' => 'a'], 'FILE_REFERENCE_EXPIRED'));
        $this->assertSame([], $this->methods());
    }

    public function testADownloadIsRefreshed(): void
    {
        $this->client();
        $this->db->onMethodResult('messages.getSavedGifs', [], self::savedGifs([self::document(200, 'old')]));
        $this->answers = ['messages.getSavedGifs' => static fn () => self::savedGifs([self::document(200, 'new')])];
        $args = ['location' => ['_' => 'inputDocumentFileLocation', 'id' => 200, 'access_hash' => 22, 'file_reference' => new Bytes('old'), 'thumb_size' => ''], 'offset' => 0, 'limit' => 1024];
        $args = $this->db->refreshMethodCall('upload.getFile', $args, 'FILE_REFERENCE_EXPIRED');
        $this->assertSame('new', $args['location']['file_reference']);
        $this->assertSame(0, $args['offset']);
    }

    private static function multiMedia(): array
    {
        $media = static fn (array $media): array => ['_' => 'inputSingleMedia', 'media' => $media, 'message' => '', 'random_id' => 1];
        return ['peer' => 1, 'multi_media' => [
            $media(['_' => 'inputMediaPhoto', 'id' => ['_' => 'inputPhoto', 'id' => 100, 'access_hash' => 11, 'file_reference' => 'old100']]),
            $media(['_' => 'inputMediaDocument', 'id' => ['_' => 'inputDocument', 'id' => 200, 'access_hash' => 22, 'file_reference' => 'old200']]),
            $media(['_' => 'inputMediaDocument', 'id' => ['_' => 'inputDocument', 'id' => 201, 'access_hash' => 22, 'file_reference' => 'old201']]),
        ]];
    }

    public function testTheIndexOfMultiMediaIsRefreshed(): void
    {
        $this->client();
        $this->db->onMethodResult('messages.getSavedGifs', [], self::savedGifs([self::document(200, 'old200'), self::document(201, 'old201')]));
        $this->db->onMethodResult('photos.getUserPhotos', ['user_id' => ['_' => 'inputUserSelf']], ['_' => 'photos.photos', 'photos' => [self::photo(100, 'old100')], 'users' => []]);
        $this->answers = ['messages.getSavedGifs' => static fn () => self::savedGifs([self::document(200, 'new200'), self::document(201, 'new201')])];

        $args = $this->db->refreshMethodCall('messages.sendMultiMedia', self::multiMedia(), 'FILE_REFERENCE_1_EXPIRED');
        // Only the media at index 1 is refreshed (and its new file reference swapped in).
        $this->assertSame(['messages.getSavedGifs'], $this->methods());
        $this->assertSame(
            ['old100', 'new200', 'old201'],
            array_map(static fn (array $media): string => $media['media']['id']['file_reference'], $args['multi_media']),
        );
        // The other document of the saved GIFs was refreshed too: it's swapped in when the call is serialized.
        $this->assertSame('new201', $this->db->getReference(ReferenceDatabase::DOCUMENT, self::document(201, 'old201')));
    }

    public function testTheIndexOfPaidMediaIsRefreshed(): void
    {
        $this->client();
        $this->db->onMethodResult('messages.getSavedGifs', [], self::savedGifs([self::document(200, 'old200')]));
        $this->db->onMethodResult('photos.getUserPhotos', ['user_id' => ['_' => 'inputUserSelf']], ['_' => 'photos.photos', 'photos' => [self::photo(100, 'old100')], 'users' => []]);
        $this->answers = [
            'messages.getSavedGifs' => static fn () => self::savedGifs([self::document(200, 'new200')]),
            'photos.getUserPhotos' => static fn () => ['_' => 'photos.photos', 'photos' => [self::photo(100, 'new100')], 'users' => []],
        ];
        $paid = ['peer' => 1, 'message' => '', 'media' => ['_' => 'inputMediaPaidMedia', 'stars_amount' => 1, 'extended_media' => [
            ['_' => 'inputMediaPhoto', 'id' => ['_' => 'inputPhoto', 'id' => 100, 'access_hash' => 11, 'file_reference' => 'old100']],
            ['_' => 'inputMediaDocument', 'id' => ['_' => 'inputDocument', 'id' => 200, 'access_hash' => 22, 'file_reference' => 'old200']],
        ]]];
        $args = $this->db->refreshMethodCall('messages.sendMedia', $paid, 'FILE_REFERENCE_1_INVALID');
        $this->assertSame(['messages.getSavedGifs'], $this->methods());
        $this->assertSame('old100', $args['media']['extended_media'][0]['id']['file_reference']);
        $this->assertSame('new200', $args['media']['extended_media'][1]['id']['file_reference']);

        // Without an index, all of them (the call sent the stored file references).
        $this->calls = [];
        $this->answers['messages.getSavedGifs'] = static fn () => self::savedGifs([self::document(200, 'newer200')]);
        $args = $this->db->refreshMethodCall('messages.sendMedia', $paid, 'FILE_REFERENCE_EXPIRED', ['fileIdPhoto:100' => 'old100', 'fileIdDocument:200' => 'new200']);
        $this->assertSame(['photos.getUserPhotos', 'messages.getSavedGifs'], $this->methods());
        $this->assertSame(['_' => 'inputUserSelf'], $this->calls[0][1]['user_id']);
        $this->assertSame('new100', $args['media']['extended_media'][0]['id']['file_reference']);
        $this->assertSame('newer200', $args['media']['extended_media'][1]['id']['file_reference']);
    }

    public function testAFailedCallIsRefreshedOnceThenFailsWithItsError(): void
    {
        $API = $this->client();
        $this->db->onMethodResult('messages.getSavedGifs', [], self::savedGifs([self::document(200, 'old')]));
        $this->answers = ['messages.getSavedGifs' => static fn () => self::savedGifs([self::document(200, 'new')])];
        $connection = $this->createMock(Connection::class);
        $connection->API = $API;
        $location = ['_' => 'inputDocumentFileLocation', 'id' => 200, 'access_hash' => 22, 'file_reference' => 'old', 'thumb_size' => ''];
        $message = new MTProtoOutgoingMessage($connection, ['location' => $location, 'offset' => 0, 'limit' => 1024], 'upload.getFile', 'upload.File', true, false, SpecialMethodType::FILE_RELATED, null);

        $this->assertNull($message->refreshReferences('FILE_REFERENCE_EXPIRED', 400)->await());
        $this->assertTrue($message->refreshedFileReferences);
        $this->assertSame('new', $message->getBody()['location']['file_reference']);
        // Still a download (a FILE_MIGRATE is still a FileRedirect).
        $this->assertSame(SpecialMethodType::FILE_RELATED, $message->specialMethodType);

        // Nothing newer.
        $this->answers = ['messages.getSavedGifs' => static fn () => self::savedGifs([self::document(200, 'new')])];
        $error = $message->refreshReferences('FILE_REFERENCE_EXPIRED', 400)->await();
        $this->assertInstanceOf(\Closure::class, $error);
        $error = $error();
        $this->assertInstanceOf(FileReferenceExpiredError::class, $error);
        $this->assertSame('FILE_REFERENCE_EXPIRED', $error->rpc);
    }

    /**
     * methodRecall() only handles a refresh future resolving to null or to a closure: a failed one gave the caller
     * the exception as its result, and failed an unobserved future.
     */
    public function testAFailingRefreshFailsWithTheErrorOfTheCall(): void
    {
        $API = $this->client();
        $this->db->onMethodResult('messages.getSavedGifs', [], self::savedGifs([self::document(200, 'old')]));
        // As a database read error would.
        (new ReflectionProperty($this->db, 'sources'))->setValue($this->db, new class([]) extends MemoryArray {
            public function get(string|int $key): mixed
            {
                throw new \RuntimeException('Database error');
            }
        });
        (new ReflectionProperty($this->db, 'pendingSources'))->setValue($this->db, []);
        $connection = $this->createMock(Connection::class);
        $connection->API = $API;
        $location = ['_' => 'inputDocumentFileLocation', 'id' => 200, 'access_hash' => 22, 'file_reference' => 'old', 'thumb_size' => ''];
        $message = new MTProtoOutgoingMessage($connection, ['location' => $location, 'offset' => 0, 'limit' => 1024], 'upload.getFile', 'upload.File', true, false, SpecialMethodType::FILE_RELATED, null);

        $error = $message->refreshReferences('FILE_REFERENCE_EXPIRED', 400)->await();
        $this->assertInstanceOf(\Closure::class, $error);
        $this->assertInstanceOf(FileReferenceExpiredError::class, $error());
    }

    /**
     * The file reference changed after the call was sent (a concurrent refresh, e.g. of another chunk of a
     * download): the stored one is newer than the one that failed, and is used without refreshing. Compared to the
     * stored one instead, the refresh found no change and the call failed.
     */
    public function testAReferenceStoredAfterTheCallWasSentIsUsed(): void
    {
        $this->client();
        $this->db->onMethodResult('messages.getSavedGifs', [], self::savedGifs([self::document(200, 'old')]));
        $location = ['_' => 'inputDocumentFileLocation', 'id' => 200, 'access_hash' => 22, 'file_reference' => 'stale', 'thumb_size' => ''];
        [, $sent] = $this->db->serializeCall(fn () => $this->API->getTL()->serializeMethod('upload.getFile', ['location' => $location, 'offset' => 0, 'limit' => 1024]));
        // The stored one was sent, not the one of the arguments.
        $this->assertSame(['fileIdDocument:200' => 'old'], $sent);
        $this->db->onMethodResult('messages.getSavedGifs', [], self::savedGifs([self::document(200, 'new')]));

        $args = $this->db->refreshMethodCall('upload.getFile', ['location' => $location, 'offset' => 0, 'limit' => 1024], 'FILE_REFERENCE_EXPIRED', $sent);
        $this->assertSame('new', $args['location']['file_reference']);
        $this->assertSame([], $this->methods());
    }

    public function testAnEmptyFileReferenceKeepsTheLastOne(): void
    {
        $this->client();
        $this->db->onMethodResult('messages.getSavedGifs', [], self::savedGifs([self::document(200, 'last')]));
        $this->db->onMethodResult('help.getPremiumPromo', [], self::premiumPromo([self::document(200, '')]));
        $this->assertSame('last', $this->db->getReference(ReferenceDatabase::DOCUMENT, self::document(200, 'other')));
        // Still a source.
        $this->assertSame(['fileSourceSavedGifs', 'fileSourcePremiumPromo'], array_column($this->sources('fileIdDocument:200'), '_'));
    }

    public function testConcurrentRefreshesOfAFileShareTheResult(): void
    {
        $this->client();
        $this->db->onMethodResult('messages.getSavedGifs', [], self::savedGifs([self::document(200, 'old')]));
        $this->answers = ['messages.getSavedGifs' => static fn () => self::savedGifs([self::document(200, 'new')])];
        $refresh = fn () => async(fn () => $this->db->refreshFileId(['_' => 'fileIdDocument', 'id' => 200], 'old'));
        $this->assertSame(['new', 'new', 'new'], await([$refresh(), $refresh(), $refresh()]));
        $this->assertSame(['messages.getSavedGifs'], $this->methods());
    }

    public function testOutgoingLocationsUseTheLastFileReference(): void
    {
        $this->client();
        $this->db->onMethodResult('messages.getSavedGifs', [], self::savedGifs([self::document(200, 'last')]));
        $serialized = $this->API->getTL()->serializeMethod('upload.getFile', [
            'location' => ['_' => 'inputDocumentFileLocation', 'id' => 200, 'access_hash' => 22, 'file_reference' => 'stale', 'thumb_size' => ''],
            'offset' => 0,
            'limit' => 1024,
        ]);
        $this->assertStringContainsString("\x04last", $serialized);
        $this->assertStringNotContainsString('stale', $serialized);

        $serialized = $this->API->getTL()->serializeMethod('messages.sendMedia', [
            'peer' => ['_' => 'inputPeerSelf'],
            'message' => '',
            'random_id' => 1,
            'media' => ['_' => 'inputMediaDocument', 'id' => ['_' => 'inputDocument', 'id' => 200, 'access_hash' => 22, 'file_reference' => 'stale']],
        ]);
        $this->assertStringContainsString("\x04last", $serialized);
        // Unknown files keep theirs.
        $serialized = $this->API->getTL()->serializeMethod('messages.sendMedia', [
            'peer' => ['_' => 'inputPeerSelf'],
            'message' => '',
            'random_id' => 1,
            'media' => ['_' => 'inputMediaDocument', 'id' => ['_' => 'inputDocument', 'id' => 201, 'access_hash' => 22, 'file_reference' => 'given']],
        ]);
        $this->assertStringContainsString("\x05given", $serialized);
    }

    public function testTheReferencesAreWrittenToTheDatabase(): void
    {
        $references = new MemoryArray([]);
        $this->client($references);
        $this->db->onMethodResult('messages.getSavedGifs', [], self::savedGifs([self::document(200, 'old')]));
        $this->db->onMethodResult('help.getPremiumPromo', [], self::premiumPromo([self::document(200, 'new')]));
        delay(0.01);
        $this->assertSame(['fileIdDocument:200' => 'new'], $references->getArrayCopy());
        $sources = (new ReflectionProperty($this->db, 'sources'))->getValue($this->db)->getArrayCopy();
        $this->assertSame(['fileIdDocument:200' => [['_' => 'fileSourceSavedGifs'], ['_' => 'fileSourcePremiumPromo']]], $sources);
        $this->assertSame([], (new ReflectionProperty($this->db, 'pendingReferences'))->getValue($this->db));
        $this->assertSame([], (new ReflectionProperty($this->db, 'pendingSources'))->getValue($this->db));
        // Seen again in the saved GIFs: the most recent one.
        $this->db->onMethodResult('messages.getSavedGifs', [], self::savedGifs([self::document(200, 'new')]));
        delay(0.01);
        $sources = (new ReflectionProperty($this->db, 'sources'))->getValue($this->db)->getArrayCopy();
        $this->assertSame(['fileIdDocument:200' => [['_' => 'fileSourcePremiumPromo'], ['_' => 'fileSourceSavedGifs']]], $sources);
    }

    /**
     * magnaluna (8.11.1): "Trying to add origin to constructor channelFull with no origin context set".
     *
     * The old reference database kept one stack of origin contexts for all the connections, pushed and popped by
     * deserialization callbacks, and emptied by every read loop after each message. With the session's callbacks
     * ordered as after a wakeup, PeerDatabase::addFullChat runs before ReferenceDatabase::addOrigin for the
     * channelFull of channels.getFullChannel: it builds the Photo of the channel, whose download info reads the
     * file reference from the database, which suspends the fiber with a MySQL backend. Another read loop finished a
     * message meanwhile and emptied the stack (logging "Found 1 pending contexts" for the channelFull context and
     * for its photo), so addOrigin found no context.
     *
     * The traversal of the file reference map runs on the deserialized result, with its own state.
     */
    public function testTheChannelFullOfMagnalunaWithASuspendingDatabase(): void
    {
        $references = new class([]) extends MemoryArray {
            public int $suspended = 0;
            public function get(string|int $key): mixed
            {
                // Like a database query.
                $this->suspended++;
                delay(0.002);
                return parent::get($key);
            }
        };
        $this->client($references);
        $deserialize = async(fn () => $this->API->getTL()->deserialize(
            FileReferenceMapTest::channelFullPayload(),
            ['type' => '', 'connection' => null, 'encrypted' => true],
        ));
        // Another connection's read loop finishing a message meanwhile.
        $other = async(function (): void {
            delay(0.001);
            $this->API->minDatabase->reset();
        });
        $message = $deserialize->await();
        $other->await();
        $this->assertGreaterThan(0, $references->suspended, 'The database was not read while deserializing');
        $this->assertSame('channelFull', $message['result']['full_chat']['_']);

        $channel = -1000000000000 - FileReferenceMapTest::CHANNEL_FULL_CHANNEL;
        $this->db->onMethodResult('channels.getFullChannel', ['channel' => $channel], $message['result']);
        $photo = $message['result']['full_chat']['chat_photo'];
        $this->assertSame(FileReferenceMapTest::CHANNEL_FULL_PHOTO, $photo['id']);
        $this->assertSame((string) $photo['file_reference'], $this->db->getReference(ReferenceDatabase::PHOTO, ['id' => $photo['id']]));
        $this->assertSame(
            [['_' => 'fileSourceChannelFull', 'channel' => FileReferenceMapTest::CHANNEL_FULL_CHANNEL]],
            $this->sources('fileIdPhoto:'.FileReferenceMapTest::CHANNEL_FULL_PHOTO),
        );
    }

    public function testTheStateOfTheLocationDatabaseIsDropped(): void
    {
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;
            return true;
        }, E_DEPRECATED | E_USER_DEPRECATED);
        try {
            $class = ReferenceDatabase::class;
            // The properties of version 1, through its __sleep().
            $data = [
                "\0$class\0db" => new MemoryArray(['0'.bin2hex(pack('q', 200)) => ['reference' => 'old', 'origins' => []]]),
                "\0$class\0pendingDb" => ['location' => ['old', 3, ['peer' => 1, 'msg_id' => 2]]],
                "\0$class\0v" => 1,
            ];
            $db = unserialize('O:'.\strlen($class).':"'.$class.'"'.substr(serialize($data), 1));
        } finally {
            restore_error_handler();
        }
        $this->assertInstanceOf(ReferenceDatabase::class, $db);
        $this->assertSame([], $deprecations);
        $dynamic = array_filter((new ReflectionObject($db))->getProperties(), static fn ($p) => !$p->isDefault());
        $this->assertSame([], array_values($dynamic));
        $this->assertSame(1, (new ReflectionProperty($db, 'v'))->getValue($db));

        // init() clears the location database of version 1.
        $API = $this->client();
        (new ReflectionProperty($db, 'API'))->setValue($db, $API);
        (new ReflectionProperty($db, 'sources'))->setValue($db, new MemoryArray([]));
        $db->init();
        $this->assertSame(2, (new ReflectionProperty($db, 'v'))->getValue($db));
        $this->assertSame([], (new ReflectionProperty($db, 'db'))->getValue($db)->getArrayCopy());
        $this->assertSame('given', $db->getReference(ReferenceDatabase::DOCUMENT, self::document(200, 'given')));
    }
}
