<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Amir Hossein Jafari <amirhosseinjafari8228@gmail.com>
 * @copyright 2016-2023 Amir Hossein Jafari <amirhosseinjafari8228@gmail.com>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Story;

use danog\MadelineProto\EventHandler\AbstractPrivacy;
use danog\MadelineProto\EventHandler\AbstractStory;
use danog\MadelineProto\EventHandler\Media\Gif;
use danog\MadelineProto\EventHandler\Media\Photo;
use danog\MadelineProto\EventHandler\Media\Video;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Message\Entities\MessageEntity;
use danog\MadelineProto\EventHandler\Privacy;
use danog\MadelineProto\EventHandler\Story\StoryReaction;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\ParseMode;

/**
 *
 */
final class Story extends AbstractStory
{
    /** */
    public readonly bool $pinned;

    /** */
    public readonly bool $public;

    /** */
    public readonly bool $closeFriends;

    /** */
    public readonly bool $min;

    /** Whether this media is protected */
    public readonly bool $protected;

    /** */
    public readonly bool $edited;

    /** */
    public readonly bool $contacts;

    /** */
    public readonly bool $selectedContacts;

    /** When was the story sent */
    public readonly int $date;

    /** Expiration date of the story */
    public readonly int $expireDate;

    /** */
    public readonly ?string $caption;

    /** @var list<MessageEntity> Message [entities](https://core.telegram.org/api/entities) for styled text */
    public readonly array $entities;

    /** Attached media. */
    public readonly Gif|Photo|Video $media;

    /** */
    //public readonly ?array $mediaAreas; //!

    /** @var list<Privacy|AbstractPrivacy> */
    public readonly array $privacy;

    /** Our message reaction? */
    public readonly int|string|null $sentReaction; 

    /** */
    public readonly ?int $reactionCount;

    /** */
    public readonly ?int $viewsCount;

    /** @var list<int> */
    public readonly array $recentViewers;

    /** @internal */
    public function __construct(MTProto $API, array $rawStory)
    {
        parent::__construct($API, $rawStory);
        $rawStory = $rawStory['story'];

        $this->pinned = $rawStory['pinned'];
        $this->public = $rawStory['public'];
        $this->closeFriends = $rawStory['close_friends'];
        $this->min = $rawStory['min'];
        $this->protected = $rawStory['noforwards'];
        $this->edited = $rawStory['edited'];
        $this->contacts = $rawStory['contacts'];
        $this->selectedContacts = $rawStory['selected_contacts'];

        $this->date = $rawStory['date'];
        $this->expireDate = $rawStory['expire_date'];

        $this->media = $API->wrapMedia($rawStory['media'], $this->protected);
        $this->entities = MessageEntity::fromRawEntities($rawStory['entities'] ?? []);
        $this->privacy = Privacy::fromRawPrivacy($rawStory['privacy'] ?? []);

        $this->recentViewers = $rawStory['views']['recent_viewers'] ?? [];
        $this->reactionCount = $rawStory['views']['views_count'] ?? null;
        $this->viewsCount = $rawStory['views']['reactions_count'] ?? null;

        $this->caption = $rawStory['caption'] ?? null;
        //$this->mediaAreas = $rawStory['mediaAreas'] ?? null; //!
        $this->sentReaction = $rawStory['sent_reaction']['emoticon'] ?? $rawStory['sent_reaction']['document_id'] ?? null;   
    }

    public function reply(
        string $message,
        ParseMode $parseMode = ParseMode::TEXT,
        ?array $replyMarkup = null,
        ?int $scheduleDate = null,
        bool $silent = false,
        bool $noForwards = false,
        bool $background = false,
        bool $clearDraft = false,
        bool $noWebpage = false,
        bool $updateStickersetsOrder = false,
    ): Message {
        $client = $this->getClient();
        $result = $client->methodCallAsyncRead(
            'messages.sendMessage',
            [
                'peer' => $this->userId,
                'message' => $message,
                'parse_mode' => $parseMode,
                'reply_to' => ['_' => 'inputReplyToStory', 'user_id' => $this->userId, 'story_id' => $this->id],
                'reply_markup' => $replyMarkup,
                'schedule_date' => $scheduleDate,
                'silent' => $silent,
                'noforwards' => $noForwards,
                'background' => $background,
                'clear_draft' => $clearDraft,
                'no_webpage' => $noWebpage,
                'update_stickersets_order' => $updateStickersetsOrder
            ]
        );
        if (isset($result['_'])) {
            return $client->wrapMessage($client->extractMessage($result));
        }

        $last = null;
        foreach ($result as $updates) {
            /** @var Message */
            $new = $client->wrapMessage($client->extractMessage($updates));
            if ($last) {
                $last->nextSent = $new;
            } else {
                $first = $new;
            }
            $last = $new;
        }
        return $first;
    }

    /**
     * 
     *
     * @return void
     */
    public function delete(): void
    {
        $this->getClient()->methodCallAsyncRead(
            'stories.deleteStories',
            [
                'id' => [$this->id],
            ]
        );
    }

    /**
     * 
     *
     * @return string
     */
    public function exportLink(): string
    {
        return $this->getClient()->methodCallAsyncRead(
            'stories.exportStoryLink',
            [
                'user_id' => $this->userId,
                'id' => $this->id,
            ]
        )['link'];
    }

    /**
     * 
     *
     * @param array $reason
     * @param string $message
     * @return boolean
     */
    public function report(array $reason, string $message = ''): bool
    {
        return $this->getClient()->methodCallAsyncRead(
            'stories.exportStoryLink',
            [
                'user_id' => $this->userId,
                'id' => $this->id,
                'reason' => $reason,
                'message' => $message,
            ]
        );
    }

    /**
     * 
     *
     * @return void
     */
    public function pin(): void
    {
        $this->getClient()->methodCallAsyncRead(
            'stories.togglePinned',
            [
                'id' => $this->id,
                'pinned' => true,
            ]
        );
    }

    /**
     * 
     *
     * @return void
     */
    public function unpin(): void
    {
        $this->getClient()->methodCallAsyncRead(
            'stories.togglePinned',
            [
                'id' => $this->id,
                'pinned' => false,
            ]
        );
    }
    /**
     * 
     *
     * @return boolean
     */
    public function read(): bool
    {
        return $this->getClient()->methodCallAsyncRead(
            'stories.incrementStoryViews',
            [
                'user_id' => $this->userId,
                'id' => $this->id,
            ]
        );
    }

    /**
     * 
     *
     * @param integer|string|null|null $reaction
     * @param boolean $recent
     * @return StoryReaction
     */
    public function addReaction(int|string|null $reaction = null, bool $recent = true): StoryReaction
    {
        $client = $this->getClient();
        $result = $client->methodCallAsyncRead(
            'stories.sendReaction',
            [
                'add_to_recent' => $recent,
                'user_id' => $this->userId,
                'id' => $this->id,
                'reaction' => \is_int($reaction)
                ? [['_' => 'reactionCustomEmoji', 'document_id' => $reaction]]
                : [['_' => 'reactionEmoji', 'emoticon' => $reaction]],
            ]
        )['updates'][0];
        return $client->wrapUpdate($result);
    }

    /**
     * 
     *
     * @param boolean $recent
     * @return StoryReaction
     */
    public function delReaction(bool $recent = true): StoryReaction
    {
        $client = $this->getClient();
        $result = $client->methodCallAsyncRead(
            'stories.sendReaction',
            [
                'add_to_recent' => $recent,
                'user_id' => $this->userId,
                'id' => $this->id,
            ]
        )['updates'][0];
        return $client->wrapUpdate($result);
    }
}
