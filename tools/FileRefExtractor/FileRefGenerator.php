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

namespace danog\MadelineProto\FileRefExtractor;

use AssertionError;
use Closure;
use danog\MadelineProto\FileRefExtractor\BuildMode\Ast;
use danog\MadelineProto\FileRefExtractor\Ops\ArrayOp;
use danog\MadelineProto\FileRefExtractor\Ops\CallOp;
use danog\MadelineProto\FileRefExtractor\Ops\ConstructorOp;
use danog\MadelineProto\FileRefExtractor\Ops\CopyMethodCallOp;
use danog\MadelineProto\FileRefExtractor\Ops\CopyOp;
use danog\MadelineProto\FileRefExtractor\Ops\GetInputChannelOp;
use danog\MadelineProto\FileRefExtractor\Ops\GetInputPeerOp;
use danog\MadelineProto\FileRefExtractor\Ops\GetInputStickerSet;
use danog\MadelineProto\FileRefExtractor\Ops\GetInputUserOp;
use danog\MadelineProto\FileRefExtractor\Ops\GetMessageOp;
use danog\MadelineProto\FileRefExtractor\Ops\Noop;
use danog\MadelineProto\FileRefExtractor\Ops\PrimitiveLiteralOp;
use danog\MadelineProto\FileRefExtractor\Ops\ThemeFormatOp;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\TL\TL;
use Webmozart\Assert\Assert;

final class FileRefGenerator
{
    public static function generate(int $layer, string $inputSchema, string $outputFile, string $outputFileJson): void
    {
        Magic::start(false);
        $schema = new TLSchema;

        $schema->setAPISchema($inputSchema);

        $TL = new TL(null);
        $TL->init($schema);

        $blacklistedPredicates = array_fill_keys(array_column($TL->getConstructors()->by_id, 'predicate'), true)
            + array_fill_keys(array_column($TL->getMethods()->by_id, 'method'), true);
        unset($blacklistedPredicates['boolTrue'], $blacklistedPredicates['boolFalse'], $blacklistedPredicates['true'], $blacklistedPredicates['vector']);

        $TL = new TLWrapper($TL);
        $locations = [];
        Logger::log("Generating file reference map for layer $layer...");

        foreach ($TL->getConstructorsOfType('Message') as $constructor => $_) {
            if ($constructor === 'messageEmpty') {
                continue;
            }
            $locations[$constructor][] = new GetMessageOp(
                new GetInputPeerOp(new Path([[$constructor, 'peer_id']])),
                new CopyOp([[$constructor, 'id']]),
                $constructor === 'message' ? new CopyOp([[$constructor, 'quick_reply_shortcut_id', Path::FLAG_PASSTHROUGH]]) : null,
                'fileSourceMessage',
            );
        }

        $locations['message'][] = new CallOp(
            'messages.getScheduledMessages',
            [
                'peer' => new GetInputPeerOp(new Path([['messages.getScheduledMessages', 'peer']], true)),
                'id' => new ArrayOp(new CopyOp([['message', 'id']])),
            ],
            'fileSourceScheduledMessage'
        );

        $locations['message'][] = new CallOp(
            'messages.getScheduledMessages',
            [
                'peer' => new GetInputPeerOp(new Path([['updateNewScheduledMessage', 'message'], ['message', 'peer_id']], true)),
                'id' => new ArrayOp(new CopyOp([['message', 'id']])),
            ],
            'fileSourceScheduledMessage'
        );

        $storyMethods = [];
        foreach (['stories.Stories'] as $t) {
            foreach ($TL->getMethodsOfType($t) as $method => $_) {
                $storyMethods[$method] = true;
                $locations['storyItem'][] = new CallOp(
                    'stories.getStoriesByID',
                    [
                        'id' => new ArrayOp(new CopyOp([['storyItem', 'id']])),
                        'peer' => new GetInputPeerOp(new Path([[$method, 'peer']], true)),
                    ],
                    'fileSourceStory'
                );
            }
        }

        $locations['storyViewPublicRepost'][] = new CallOp(
            'stories.getStoriesByID',
            [
                'id' => new ArrayOp(new CopyOp([['storyViewPublicRepost', 'story'], ['storyItem', 'id']])),
                'peer' => new GetInputPeerOp(new Path([['storyViewPublicRepost', 'peer_id']])),
            ],
            'fileSourceStory'
        );
        $locations['storyReactionPublicRepost'][] = new CallOp(
            'stories.getStoriesByID',
            [
                'id' => new ArrayOp(new CopyOp([['storyReactionPublicRepost', 'story'], ['storyItem', 'id']])),
                'peer' => new GetInputPeerOp(new Path([['storyReactionPublicRepost', 'peer_id']])),
            ],
            'fileSourceStory'
        );

        $locations['storyItem'][] = new CallOp(
            'stories.getStoriesByID',
            [
                'id' => new ArrayOp(new CopyOp([['storyItem', 'id']])),
                'peer' => new GetInputPeerOp(new Path([['peerStories', 'peer']], true)),
            ],
            'fileSourceStory'
        );

        foreach (['foundStory', 'publicForwardStory', 'webPageAttributeStory', 'messageMediaStory'] as $c) {
            $optional = $c === 'webPageAttributeStory' || $c === 'messageMediaStory';
            $locations[$c][] = new CallOp(
                'stories.getStoriesByID',
                [
                    'id' => new ArrayOp(new CopyOp([$optional ? [$c, 'story', Path::FLAG_IF_ABSENT_ABORT] : [$c, 'story'], ['storyItem', 'id']])),
                    'peer' => new GetInputPeerOp(new Path([[$c, 'peer']])),
                ],
                'fileSourceStory'
            );
        }

        $locations['webPage'][] = CallOp::simple(
            'messages.getWebPage',
            'webPage',
            ['url' => 'url', 'hash' => new PrimitiveLiteralOp('int', 0)],
            'fileSourceWebPage'
        );

        $locations['botApp'][] = CallOp::simple('messages.getBotApp', 'botApp', [
            'app' => new ConstructorOp(
                'inputBotAppID',
                [
                    'id' => new CopyOp([['botApp', 'id']]),
                    'access_hash' => new CopyOp([['botApp', 'access_hash']]),
                ]
            ),
            'hash' => new PrimitiveLiteralOp('long', 0),
        ], 'fileSourceBotApp');

        $locations['botInfo'][] = new CallOp(
            'users.getFullUser',
            ['id' => new GetInputUserOp(new Path([['botInfo', 'user_id', Path::FLAG_IF_ABSENT_ABORT]]))],
            'fileSourceUserFull'
        );
        $locations['storyItem'][] = new CallOp('stories.getStoriesByID', [
            'id' => new ArrayOp(new CopyOp([['storyItem', 'id']])),
            'peer' => new GetInputPeerOp(new Path([['storyItem', 'from_id', Path::FLAG_IF_ABSENT_ABORT]])),
        ], 'fileSourceStory');

        //$locations['messages.getSponsoredMessages'][] = new CopyMethodCallOp('messages.getSponsoredMessages', 'fileSourceSponsoredMessage');
        $locations['messages.getSponsoredMessages'][] = new Noop('Do not store file references from sponsored messages');

        $locations['channelAdminLogEvent'][] = new CallOp(
            'channels.getAdminLog',
            [
                'channel' => new GetInputChannelOp(new Path([['channels.getAdminLog', 'channel']], true)),
                'max_id' => new CopyOp([['channelAdminLogEvent', 'id']]),
                'min_id' => new CopyOp([['channelAdminLogEvent', 'id']]),
                'limit' => new PrimitiveLiteralOp('int', 1),
                'q' => new PrimitiveLiteralOp('string', ''),
            ],
            'fileSourceAdminLog'
        );

        foreach (['stories.createAlbum', 'stories.getAlbums', 'stories.updateAlbum'] as $m) {
            $locations[$m][] = new CallOp('stories.getAlbums', [
                'peer' => new GetInputPeerOp(new Path([[$m, 'peer']])),
                'hash' => new PrimitiveLiteralOp('long', 0),
            ], 'fileSourceStoryAlbum');
        }

        $locations['bots.getPreviewMedias'][] = new CopyMethodCallOp('bots.getPreviewMedias', 'fileSourceBotPreviewMedia');
        $locations['bots.getPreviewInfo'][] = new CopyMethodCallOp('bots.getPreviewInfo', 'fileSourceBotPreviewInfo');
        $locations['bots.addPreviewMedia'][] = new CallOp('bots.getPreviewInfo', [
            'bot' => new GetInputUserOp(new Path([['bots.addPreviewMedia', 'bot']])),
            'lang_code' => new CopyOp([['bots.addPreviewMedia', 'lang_code']]),
        ], 'fileSourceBotPreviewInfo');
        $locations['bots.editPreviewMedia'][] = new CallOp('bots.getPreviewInfo', [
            'bot' => new GetInputUserOp(new Path([['bots.editPreviewMedia', 'bot']])),
            'lang_code' => new CopyOp([['bots.editPreviewMedia', 'lang_code']]),
        ], 'fileSourceBotPreviewInfo');

        $locations['updateMessageExtendedMedia'][] = new CallOp(
            'messages.getExtendedMedia',
            [
                'id' => new ArrayOp(new CopyOp([['updateMessageExtendedMedia', 'msg_id']])),
                'peer' => new GetInputPeerOp(new Path([['updateMessageExtendedMedia', 'peer']])),
            ],
            'fileSourcePaidMedia'
        );
        $locations['userFull'][] = new CallOp(
            'users.getFullUser',
            [
                'id' => new GetInputUserOp(new Path([['userFull', 'id']])),
            ],
            'fileSourceUserFull'
        );
        $locations['chatFull'][] = new CallOp(
            'messages.getFullChat',
            [
                'chat_id' => new CopyOp([['chatFull', 'id']]),
            ],
            'fileSourceChatFull'
        );
        $locations['channelFull'][] = new CallOp(
            'channels.getFullChannel',
            [
                'channel' => new GetInputChannelOp(new Path([['channelFull', 'id']])),
            ],
            'fileSourceChannelFull'
        );
        $locations['communityFull'][] = new CallOp(
            'channels.getFullChannel',
            [
                'channel' => new GetInputChannelOp(new Path([['communityFull', 'id']])),
            ],
            'fileSourceCommunityFull'
        );
        $locations['help.getPremiumPromo'][] = new CopyMethodCallOp('help.getPremiumPromo', 'fileSourcePremiumPromo');

        $locations['help.getAppUpdate'][] = new Noop("Don't handle file references from ephemeral app update info");
        $locations['help.getRecentMeUrls'][] = new Noop("Don't handle file references from recent t.me URLs");

        $stickerMethods = [
            'messages.getAttachedStickers' => true,
        ];
        foreach ([
            'messages.FeaturedStickers',
            'messages.StickerSet',
            'messages.ArchivedStickers',
            'messages.Stickers',
            'messages.StickerSetInstallResult',
            'messages.FoundStickerSets',
            'messages.MyStickers',
            'messages.FavedStickers',
            'messages.FoundStickers',
        ] as $stickerType) {
            foreach ($TL->getMethodsOfType($stickerType) as $method => $_) {
                $stickerMethods[$method] = true;
            }
        }

        $starMethods = [];
        foreach ($TL->getMethodsOfType('payments.StarsStatus') as $method => $_) {
            $starMethods[$method] = true;
            $locations['starsTransaction'][] = new CallOp(
                'payments.getStarsTransactionsByID',
                [
                    'peer' => new GetInputPeerOp(new Path([[$method, 'peer']], true)),
                    ...($method === 'payments.getStarsSubscriptions' ? [] : ['ton' => new CopyOp(new Path([[$method, 'ton', Path::FLAG_PASSTHROUGH]], true))]),
                    'id' => new ArrayOp(new ConstructorOp(
                        'inputStarsTransaction',
                        [
                            'id' => new CopyOp([['starsTransaction', 'id']]),
                            'refund' => new CopyOp([['starsTransaction', 'refund', Path::FLAG_PASSTHROUGH]]),
                        ]
                    )),
                ],
                'fileSourceStarsTransaction'
            );
        }
        $locations['attachMenuBot'][] = new CallOp(
            'messages.getAttachMenuBot',
            ['bot' => new GetInputUserOp(new Path([['attachMenuBot', 'bot_id']]))],
            'fileSourceAttachMenuBot'
        );
        $locations['theme'][] = new CallOp(
            'account.getTheme',
            [
                'theme' => new ConstructorOp(
                    'inputTheme',
                    [
                        'id' => new CopyOp([['theme', 'id']]),
                        'access_hash' => new CopyOp([['theme', 'access_hash']]),
                    ]
                ),
                'format' => new ThemeFormatOp(),
            ],
            'fileSourceTheme'
        );
        $locations['wallPaper'][] = new CallOp(
            'account.getWallPaper',
            [
                'wallpaper' => new ConstructorOp(
                    'inputWallPaper',
                    [
                        'id' => new CopyOp([['wallPaper', 'id']]),
                        'access_hash' => new CopyOp([['wallPaper', 'access_hash']]),
                    ]
                ),
            ],
            'fileSourceWallPaper'
        );

        // Multiple variations to handle references from covers in StickerSetCovered and messages.StickerSet
        foreach (['stickerSetMultiCovered', 'stickerSetFullCovered'] as $c) {
            $locations[$c][] = new CallOp(
                'messages.getStickerSet',
                [
                    'stickerset' => new GetInputStickerSet(new Path([[$c, 'set']])),
                    'hash' => new PrimitiveLiteralOp('int', 0),
                ],
                'fileSourceStickerSet'
            );
        }
        $locations['messages.stickerSet'][] = new CallOp(
            'messages.getStickerSet',
            [
                'stickerset' => new GetInputStickerSet(new Path([['messages.stickerSet', 'set']])),
                'hash' => new PrimitiveLiteralOp('int', 0),
            ],
            'fileSourceStickerSet'
        );
        $locations['messages.savedGifs'][] = new CallOp('messages.getSavedGifs', ['hash' => new PrimitiveLiteralOp('long', 0)], 'fileSourceSavedGifs');
        foreach (['account.savedRingtones', 'account.savedRingtoneConverted', 'account.uploadRingtone'] as $c) {
            $locations[$c][] = new CallOp('account.getSavedRingtones', ['hash' => new PrimitiveLiteralOp('long', 0)], 'fileSourceSavedRingtones');
        }

        $locations['draftMessage'][] = new Noop('Do not store references from drafts');
        $locations['recentMeUrlChatInvite'][] = new Noop('Do not store references based on chat invite links');
        $locations['messages.checkChatInvite'][] = new Noop('Do not store references based on chat invite links');

        $locations['messages.availableEffects'][] = new CallOp(
            'messages.getAvailableEffects',
            ['hash' => new PrimitiveLiteralOp('int', 0)],
            'fileSourceAvailableEffects'
        );
        $locations['messages.availableReactions'][] = new CallOp(
            'messages.getAvailableReactions',
            ['hash' => new PrimitiveLiteralOp('int', 0)],
            'fileSourceAvailableReactions'
        );

        $locations['updateMessagePoll'][] = new GetMessageOp(
            new GetInputPeerOp(new Path([['updateMessagePoll', 'peer', Path::FLAG_IF_ABSENT_ABORT]])),
            new CopyOp([['updateMessagePoll', 'msg_id', Path::FLAG_IF_ABSENT_ABORT]]),
            null,
            'fileSourceMessage',
        );

        $locations['photo'][] = new CallOp(
            'photos.getUserPhotos',
            [
                'user_id' => new GetInputUserOp(new Path([['photos.getUserPhotos', 'user_id']], true)),
                'offset' => new PrimitiveLiteralOp('int', -1),
                'max_id' => new CopyOp([['photo', 'id']]),
                'limit' => new PrimitiveLiteralOp('int', 1),
            ],
            'fileSourceUserProfilePhoto'
        );

        foreach (['photos.updateProfilePhoto', 'photos.uploadProfilePhoto'] as $method) {
            $locations[$method][] = new CallOp(
                'photos.getUserPhotos',
                [
                    'user_id' => new GetInputUserOp(new Path(
                        [[
                            $method,
                            'bot',
                            new ConstructorOp(
                                'inputUserSelf',
                                []
                            ),
                        ]]
                    )),
                    'offset' => new PrimitiveLiteralOp('int', -1),
                    'max_id' => new CopyOp([[$method, ''], ['photos.photo', 'photo'], ['photo', 'id']]),
                    'limit' => new PrimitiveLiteralOp('int', 1),
                ],
                'fileSourceUserProfilePhoto'
            );
        }
        $locations['photos.uploadContactProfilePhoto'][] = new CallOp(
            'photos.getUserPhotos',
            [
                'user_id' => new GetInputUserOp(
                    new Path([['photos.uploadContactProfilePhoto', 'user_id']]),
                ),
                'offset' => new PrimitiveLiteralOp('int', -1),
                'max_id' => new CopyOp([['photos.uploadContactProfilePhoto', ''], ['photos.photo', 'photo'], ['photo', 'id']]),
                'limit' => new PrimitiveLiteralOp('int', 1),
            ],
            'fileSourceUserProfilePhoto'
        );
        $locations['messages.getInlineBotResults'][]= new Noop('Inline bot results are ephemeral');
        $locations['messages.getPreparedInlineMessage'][]= new Noop('Inline bot results are ephemeral');

        $locations['messages.uploadMedia'][]= new Noop('A freshly uploaded media file will obtain a context only once it is sent to a chat');
        $locations['messages.uploadImportedMedia'][]= new Noop('A freshly uploaded media file will obtain a context only once it is sent to a chat');

        $locations['document'][] = new CallOp(
            'messages.getStickerSet',
            [
                'stickerset' => new GetInputStickerSet(new Path([['document', 'attributes']])),
                'hash' => new PrimitiveLiteralOp('int', 0),
            ],
            'fileSourceStickerSet'
        );
        $locations['messages.getDocumentByHash'][] = new CopyMethodCallOp('messages.getDocumentByHash', 'fileSourceDocumentByHash');
        $locations['updateServiceNotification'][] = new Noop('Cannot refetch service notifications');

        $locations['messages.getWebPagePreview'][] = new Noop("No locations are added for the method call, as it doesn't use persistent IDs as input; the location is instead extracted from the persistent IDs in the returned WebPage object");

        foreach (['users.getSavedMusic', 'users.getSavedMusicByID'] as $m) {
            $locations['document'][] = new CallOp(
                'users.getSavedMusicByID',
                [
                    'id' => new GetInputUserOp(new Path([[$m, 'id']], true, 'user_id')),
                    'documents' => new ArrayOp(
                        new ConstructorOp(
                            'inputDocument',
                            [
                                'id' => new CopyOp([['document', 'id']]),
                                'access_hash' => new CopyOp([['document', 'access_hash']]),
                                'file_reference' => new PrimitiveLiteralOp('bytes', ''),
                            ],
                        ),
                    ),
                ],
                'fileSourceSavedMusic'
            );
        }
        $locations['userFull'][] = new CallOp(
            'users.getSavedMusicByID',
            [
                'id' => new GetInputUserOp(new Path([['userFull', 'id']], customName: 'user_id')),
                'documents' => new ArrayOp(
                    new ConstructorOp(
                        'inputDocument',
                        [
                            'id' => new CopyOp([['userFull', 'saved_music', Path::FLAG_IF_ABSENT_ABORT], ['document', 'id']]),
                            'access_hash' => new CopyOp([['userFull', 'saved_music', Path::FLAG_IF_ABSENT_ABORT], ['document', 'access_hash']]),
                            'file_reference' => new PrimitiveLiteralOp('bytes', ''),
                        ],
                    ),
                ),
            ],
            'fileSourceSavedMusic'
        );

        $locations['ephemeral.getWelcomeMessages'][] = new CallOp(
            'ephemeral.getWelcomeMessages',
            [
                'peer' => new GetInputPeerOp(new Path([['ephemeral.getWelcomeMessages', 'peer']])),
                'hash' => new PrimitiveLiteralOp('long', 0),
            ],
            'fileSourceWelcomeMessages'
        );

        // Ignore these for now
        foreach (['payments.ResaleStarGifts', 'payments.StarGiftUpgradePreview', 'StarGift', 'StarGiftCollection', 'payments.StarGiftCollections', 'payments.StarGiftUpgradeAttributes'] as $type) {
            foreach ($TL->getConstructorsOfType($type) as $constructor => $_) {
                if ($constructor === 'payments.starGiftCollectionsNotModified') {
                    continue;
                }
                $locations[$constructor][] = new Noop('Contexts for star gifts are not yet implemented');
            }
        }

        $locations['updateEditEphemeralMessage'][] = new Noop('Do not store file references from ephemeral messages');
        $locations['updateEphemeralBotCallbackQuery'][] = new Noop('Do not store file references from ephemeral messages');
        $locations['updateNewEphemeralMessage'][] = new Noop('Do not store file references from ephemeral messages');

        $locations['messages.translatedRichMessage'][] = new Noop('Do not store file references from translated rich messages');
        $locations['messages.composedRichMessageWithAI'][] = new Noop('Do not store file references from composed rich messages');

        foreach (['updateChatUserTyping', 'updateChannelUserTyping', 'updateUserTyping'] as $type) {
            $locations[$type][] = new Noop('Documents encountered in rich text message live drafts are ephemeral');
        }
        $locations['messages.getCustomEmojiDocuments'][] = new Noop("Do not store file references in this context");

        $locations['account.uploadTheme'][] = new Noop('A freshly uploaded theme file will obtain a context only once it is created via account.createTheme');

        $incomingList = [];
        $outgoingList = $TL->tl->getMethods()->by_id;
        foreach ($TL->tl->getConstructors()->by_id as $k => $constructor) {
            if (str_starts_with($constructor['predicate'], 'input')
                && ctype_upper(substr($constructor['predicate'], \strlen('input'), 1))
            ) {
                $outgoingList[$k] = $constructor;
            } else {
                $incomingList[$k] = $constructor;
            }
        }
        $recurse = static function (Closure $onStackEnd, string $type, array &$stack, array &$stackTypes, bool $incoming) use ($TL, &$recurse, $outgoingList, $incomingList): void {
            if ($incoming) {
                if ($type === 'Update' || $type === 'Updates') {
                    $onStackEnd($stack);
                    return;
                }
                if ($type === 'PeerStories') {
                    $onStackEnd($stack);
                }
            }

            $pos = \count($stack);
            foreach ($incoming ? $incomingList : $outgoingList as $constructor) {
                $predicate = $constructor['predicate'] ?? $constructor['method'];
                if ($predicate === 'updateShortMessage' || $predicate === 'updateShortChatMessage' || $predicate === 'updateShortSentMessage') {
                    // Assume these are converted to message constructors by the client.
                    continue;
                }
                $isMethod = isset($constructor['method']);
                $t = $constructor['type'];
                $stackTypes[$t] ??= 0;
                if ($stackTypes[$t] > 1) {
                    continue;
                }
                $stackTypes[$t]++;
                foreach ($constructor['params'] as $param) {
                    if ((
                        $param['type'] === $type ||
                        (
                            isset($param['subtype'])
                            && $param['subtype'] === $type
                        )
                    )) {
                        $stack[$pos] = [$predicate, $param['name'], 0];
                        if (isset($param['pow'])) {
                            $stack[$pos][2] = Path::FLAG_IF_ABSENT_ABORT;
                        }
                        if (isset($param['subtype'])) {
                            $oldFlag = $stack[$pos][2] ?? 0;
                            $stack[$pos][2] = $oldFlag | Path::FLAG_UNPACK_ARRAY;
                        }
                        if ($isMethod) {
                            if (!$incoming) {
                                $onStackEnd($stack);
                            }
                        } else {
                            $recurse($onStackEnd, $t, $stack, $stackTypes, $incoming);
                        }
                        unset($stack[$pos]);

                    }
                }
                $stackTypes[$t]--;
            }

            if ($incoming) {
                foreach ($TL->getMethodsOfType($type, true) as $method => $data) {
                    $stack[$pos] = [$method, '', 0];
                    $onStackEnd($stack);
                }
                foreach ($TL->getMethodsOfType("Vector<$type>", true) as $method => $data) {
                    $stack[$pos] = [$method, '', Path::FLAG_UNPACK_ARRAY];
                    $onStackEnd($stack);
                }
            }
            unset($stack[$pos]);
        };

        $pre = [
            'fileSourceMessage' => [
                'flags' => '#',
                'quick_reply_shortcut_id' => 'flags.0?int',
                'peer' => 'long',
                'id' => 'int',
            ],
            'fileSourceStarsTransaction' => [
                'flags' => '#',
                'peer' => 'long',
                'id' => 'string',
                'refund' => 'flags.0?true',
                'ton' => 'flags.1?true',
            ],
        ];

        $validated = [];

        $outgoingCons = [
            'inputPhoto' => ['fileIdPhoto', 'id', 'file_reference'],
            'inputDocument' => ['fileIdDocument', 'id', 'file_reference'],
            'inputDocumentFileLocation' => ['fileIdDocument', 'id', 'file_reference'],
            'inputPhotoFileLocation' => ['fileIdPhoto', 'id', 'file_reference'],

            // Legacy
            'inputFileLocation' => false,
            'inputPhotoLegacyFileLocation' => false,
        ];
        $incomingCons = [
            'document' => ['fileIdDocument', 'id', 'file_reference'],
            'photo' => ['fileIdPhoto', 'id', 'file_reference'],
        ];
        foreach ($TL->tl->getConstructors()->by_id as $constructor) {
            foreach ($constructor['params'] as $param) {
                if ($param['name'] === 'file_reference') {
                    if (isset($outgoingCons[$constructor['predicate']])) {
                        if ($outgoingCons[$constructor['predicate']] === false) {
                            continue 2;
                        }
                        [, $id, $fileref] = $outgoingCons[$constructor['predicate']];
                        $params = array_column($constructor['params'], null, 'name');
                        Assert::keyExists($params, $id);
                        Assert::keyExists($params, $fileref);
                        Assert::eq($params[$id]['type'], 'long');
                        Assert::eq($params[$fileref]['type'], 'bytes');
                        Assert::keyNotExists($params[$id], 'pow');
                        Assert::keyNotExists($params[$fileref], 'pow');
                        continue 2;
                    }
                    if (isset($incomingCons[$constructor['predicate']])) {
                        [, $id, $fileref] = $incomingCons[$constructor['predicate']];
                        $params = array_column($constructor['params'], null, 'name');
                        Assert::keyExists($params, $id);
                        Assert::keyExists($params, $fileref);
                        Assert::eq($params[$id]['type'], 'long');
                        Assert::eq($params[$fileref]['type'], 'bytes');
                        Assert::keyNotExists($params[$id], 'pow');
                        Assert::keyNotExists($params[$fileref], 'pow');
                        continue 2;
                    }
                    throw new AssertionError("Have file_reference for {$constructor['predicate']} but not used");
                }
            }
        }

        $outgoingTraversalPairsByCons = [];
        foreach ($outgoingCons as $constructor => $contents) {
            if ($contents === false) {
                continue;
            }
            $type = $TL->tl->getConstructors()->findByPredicate($constructor)['type'];
            $stack = [[$constructor, 'file_reference', 0]];
            $stackTypes = [$type => 1];

            $outgoingTraversalPairs = [];
            $recurse(
                static function (array $stack) use (&$outgoingTraversalPairs): void {
                    foreach ($stack as $pair) {
                        if ($pair[1] === 'file_reference') {
                            continue;
                        }
                        $outgoingTraversalPairs[$pair[0]][$pair[1]] = $pair;
                    }
                },
                $type,
                $stack,
                $stackTypes,
                false,
            );
            $outgoingTraversalPairsByCons[$constructor] = $outgoingTraversalPairs;
        }
        unset($outgoingTraversalPairs);

        $incomingTraversalPairsByCons = [];
        $tmp = new Ast(blacklistedPredicates: $blacklistedPredicates, allowUnpacking: true, outputSchema: $pre);
        foreach ($incomingCons as $constructor => $_) {
            $type = ucfirst($constructor);
            $stack = [[$constructor, 'file_reference', 0]];
            $stackTypes = [$type => 1];

            $incomingTraversalPairs = [];
            $recurse(
                static function (array $stack) use ($locations, $TL, $tmp, &$incomingTraversalPairs, &$validated, $storyMethods, $starMethods, $stickerMethods): void {
                    $slice = [];
                    $hadAny = false;
                    $hadAnyNotNoop = false;
                    $tmpPairs = [];
                    $hadAnyWithNoFlags = false;
                    $skippedDueToFlags = [];
                    $top = end($stack)[0];
                    for ($x = \count($stack)-1; $x >= 0; $x--) {
                        $pair = $stack[$x];

                        if ($pair[1] !== 'file_reference') {
                            $tmpPairs[$pair[0]][$pair[1]] = $pair;
                        }

                        foreach ($locations[$pair[0]] ?? [] as $op) {
                            $normalized = $op->normalize($slice, $pair[0], false);
                            if ($normalized === null) {
                                continue;
                            }
                            if (!$normalized instanceof Noop) {
                                $hadAnyNotNoop = true;
                            }
                            $hadAny = true;
                            $normalized->build(new TLContext($TL, $tmp, $top, $TL->isConstructor($top)));
                            $validated[$pair[0]][spl_object_id($op)] = $op;

                            $normalized = $op->normalize($slice, $pair[0], true);
                            if ($normalized === null) {
                                $skippedDueToFlags []= $op;
                                continue;
                            }
                            $hadAnyWithNoFlags = true;
                        }
                        $slice[] = $pair;
                    }
                    if ($hadAnyNotNoop) {
                        foreach ($tmpPairs as $cons => $fields) {
                            $incomingTraversalPairs[$cons] ??= [];
                            foreach ($fields as $field => $part) {
                                $incomingTraversalPairs[$cons][$field] = $part;
                            }
                        }
                    }
                    if (!$hadAny) {
                        throw new AssertionError("Uncovered path: " . json_encode($stack));
                    }
                    if (!$hadAnyWithNoFlags && $skippedDueToFlags) {
                        if ($top === 'updateStory'
                            || $top === 'peerStories'
                            // The two above always have the story peer flag set.

                            || isset($storyMethods[$top])
                            || isset($starMethods[$top])

                            // The two above always have the story peer/all star flags set.

                            || $top === 'messages.getFullChat'
                            || $top === 'channels.getFullChannel'
                            || $top === 'users.getFullUser'
                            // The three above are related to botInfo, ignore as we already store a context for the chat info.

                            || isset($stickerMethods[$top])
                            || $top === 'messages.getRecentStickers'
                            || $top === 'updateNewStickerSet'
                            // The above are covered by the GetInputStickerSet document context

                            || $top === 'updateMessagePoll'
                            // It's okay
                        ) {
                            return;
                        }
                        foreach ($slice as [$cons]) {
                            if ($cons === 'webPageAttributeStory'
                                || $cons === 'messageMediaStory'
                                || $cons === 'foundStory'
                                || $cons === 'publicForwardStory'
                                || $cons === 'peerStories'
                                || $cons === 'storyViewPublicRepost'
                                || $cons === 'storyReactionPublicRepost'
                            ) {
                                // The above always have all necessary flags set
                                return;
                            }
                        }
                        var_dump($skippedDueToFlags);
                        throw new AssertionError("Uncovered path (didn't have at least one unflagged context): " . json_encode($stack));
                    }
                },
                $type,
                $stack,
                $stackTypes,
                true,
            );
            $incomingTraversalPairsByCons[$constructor] = $incomingTraversalPairs;
        }
        unset($incomingTraversalPairs);

        $diff = [];
        foreach ($locations as $constructor => $ops) {
            if (isset($validated[$constructor])) {
                $d = array_udiff($ops, $validated[$constructor], static fn ($a, $b) => spl_object_id($a) <=> spl_object_id($b));
                if ($d) {
                    $diff[$constructor] = $d;
                }
                continue;
            }
            $diff[$constructor] = $ops;
        }
        if ($diff) {
            var_dump($diff);
            throw new AssertionError("Leftover ops!");
        }

        $output = new Ast(blacklistedPredicates: $blacklistedPredicates, allowUnpacking: false, outputSchema: $pre);
        foreach ($locations as $constructor => $ops) {
            foreach ($ops as $idx => $op) {
                $op->build(new TLContext($TL, $output, $constructor, $TL->isConstructor($constructor)));
            }
        }

        $outgoingCons = array_filter($outgoingCons);
        $output->finalize(
            $TL,
            $layer,
            $outgoingCons,
            $incomingCons,
            self::fixupTraversalPairs($output, $TL, $incomingCons, $incomingTraversalPairsByCons, true),
            self::fixupTraversalPairs($output, $TL, $outgoingCons, $outgoingTraversalPairsByCons, false),
            $outputFile,
            $outputFileJson
        );

        echo("OK $layer!\n".PHP_EOL);
    }

    public static function fixupTraversalPairs(Ast $output, TLWrapper $TL, array $locations, array $pairsByCons, bool $isIncoming): array
    {
        $pairsMerged = [];
        foreach ($pairsByCons as $parentCons => $pairs) {
            foreach ($pairs as $cons => $fields) {
                Assert::notEmpty($fields, "No fields for $cons in $parentCons");
                foreach ($fields as $field => $part) {
                    $pairsMerged[$cons][$field] = $part;
                }
            }
        }

        if ($isIncoming) {
            $sources = $output->getSources();
            $parents = $output->getNeedsParentList();
        }

        $fixed = [];
        foreach ($locations as $predicate => [$cons]) {
            if ($isIncoming) {
                $fixed []= [
                    '_' => 'traverseCommitSourceLocation',
                    'type' => $TL->getConstructorOrMethod($predicate)['type'],
                    'predicate' => $predicate,
                    'stored_constructor' => $cons,
                    'push_sources' => $sources[$predicate] ?? [],
                ];
                unset($sources[$predicate]);
            } else {
                $fixed []= [
                    '_' => 'traverseSwapLocation',
                    'type' => $TL->getConstructorOrMethod($predicate)['type'],
                    'predicate' => $predicate,
                    'stored_constructor' => $cons,
                ];
            }
        }

        foreach ($pairsMerged as $cons => $fields) {

            $consIsConstructor = $TL->isConstructor($cons);
            $order = [];
            $paramTypes = [];
            foreach ($TL->getConstructorOrMethod($cons)['params'] as $idx => $param) {
                $order[$param['name']] = $idx;
                $paramTypes[$param['name']] = $param['subtype'] ?? $param['type'];
            }

            $newFields = [];
            $hasReturn = false;
            foreach ($fields as $field => $part) {
                if ($field === '') {
                    $hasReturn = true;
                    continue;
                }
                $newPart = [
                    '_' => 'traverseParam',
                    'name' => $field,
                    'type' => $paramTypes[$field],
                    'is_vector' => false,
                    'is_flag' => false,
                ];
                if (isset($part[2])) {
                    if ($part[2] instanceof TypedOp) {
                        throw new \InvalidArgumentException('Cannot use TypedOp in traverse path');
                    } elseif (\is_int($part[2])) {
                        if ($part[2] & Path::FLAG_UNPACK_ARRAY) {
                            $newPart['is_vector'] = true;
                        }
                        if ($part[2] & Path::FLAG_IF_ABSENT_ABORT) {
                            $newPart['is_flag'] = true;
                        }
                        if ($part[2] & Path::FLAG_PASSTHROUGH) {
                            $newPart['is_flag'] = true;
                        }
                    }
                }
                $newFields[] = $newPart;
            }
            usort($newFields, static fn ($a, $b) => $order[$a['name']] <=> $order[$b['name']]);

            if ($isIncoming) {
                if ($consIsConstructor) {
                    Assert::false($hasReturn, "Constructor $cons has a return value, cannot be used in incoming traversal");
                    Assert::notEmpty($newFields, "Constructor $cons does not have fields in traversal pairs but is used as a constructor");

                    $consT = $TL->getConstructorOrMethod($cons)['type'];
                    Assert::notEq($consT, 'Updates', "Constructor $cons has type Updates, cannot be used in incoming traversal as it is not a message/container constructor");

                    $fixed[] = [
                        '_' => 'traverseIncomingConstructor',
                        'predicate' => $cons,
                        'type' => $consT,
                        'params' => $newFields,
                        'push_sources' => $sources[$cons] ?? [],
                        'is_needed_parent' => isset($parents[$cons]),
                    ];
                    unset($sources[$cons], $parents[$cons]);

                } else {
                    Assert::notEmpty($hasReturn, "Method $cons does not have a return value, cannot be used in incoming traversal");
                    Assert::isEmpty($newFields, "Method $cons has fields in traversal pairs but is used as a method result");

                    $fixed[] = [
                        '_' => 'traverseMethodResult',
                        'name' => $cons,
                        'push_sources' => $sources[$cons] ?? [],
                        'is_needed_parent' => isset($parents[$cons]),
                    ];
                    unset($sources[$cons], $parents[$cons]);

                }
            } else {
                if ($consIsConstructor) {
                    Assert::false($hasReturn, "Constructor $cons has a return value, cannot be used in outgoing traversal");
                    Assert::notEmpty($newFields, "Constructor $cons does not have fields in traversal pairs but is used as a constructor");

                    $fixed[] = [
                        '_' => 'traverseOutgoingConstructor',
                        'predicate' => $cons,
                        'type' => $TL->getConstructorOrMethod($cons)['type'],
                        'params' => $newFields,
                    ];
                } else {
                    Assert::false($hasReturn, "Method $cons has a return value, cannot be used in outgoing traversal");
                    Assert::notEmpty($newFields, "Method $cons does not have fields in traversal pairs but is used as a method call");

                    $fixed[] = [
                        '_' => 'traverseMethodCall',
                        'name' => $cons,
                        'params' => $newFields,
                    ];
                }
            }
        }

        if ($isIncoming) {
            foreach ($sources as $cons => $sourceList) {
                foreach ($sourceList as $source) {
                    $source = json_encode($source);
                    throw new AssertionError("Source $source for $cons was not included in traversal pairs");
                }
            }
            foreach ($parents as $cons => $_) {
                throw new AssertionError("Needed parent $cons was not included in traversal pairs");
            }
        }
        return $fixed;
    }
}
