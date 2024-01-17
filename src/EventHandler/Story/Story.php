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

use AssertionError;
use danog\MadelineProto\EventHandler\AbstractStory;
use danog\MadelineProto\EventHandler\Media\Gif;
use danog\MadelineProto\EventHandler\Media\Photo;
use danog\MadelineProto\EventHandler\Media\Video;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Message\Entities\MessageEntity;
use danog\MadelineProto\EventHandler\Message\ReportReason;
use danog\MadelineProto\EventHandler\Privacy\RuleDestination;
use danog\MadelineProto\Ipc\Client;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\StrTools;

/**
 * Represents a Telegram story.
 */
final class Story extends AbstractStory
{
    /** Whether this story is pinned */
    public readonly bool $pinned;

    /** Whether this story is visible to everyone */
    public readonly bool $public;

    /** Whether this story is visible to only close friends of the user (@see Privacy::AllowCloseFriends) */
    public readonly bool $closeFriends;

    /** Whether this story is only visible to your countacts */
    public readonly bool $contacts;

    /** Whether this story is only visible to a select list of contacts */
    public readonly bool $selectedContacts;

    /** Whether this media is protected */
    public readonly bool $protected;

    /** Whether this story was edited */
    public readonly bool $edited;

    /** When was the story sent */
    public readonly int $date;

    /** Expiration date of the story */
    public readonly int $expireDate;

    /** Story caption */
    public readonly string $caption;

    /** @var list<MessageEntity> Message [entities](https://core.telegram.org/api/entities) for story caption */
    public readonly array $entities;

    /** Attached media. */
    public readonly Gif|Photo|Video $media;

    /** A set of physical coordinates associated to this story */
    //public readonly ?array $mediaAreas; //!

    /** @var list<RuleDestination> */
    public readonly array $privacy;

    /** Our reaction to the story */
    public readonly int|string|null $sentReaction;

    /** Reaction counter */
    public readonly ?int $reactionCount;

    /** View counter */
    public readonly ?int $views;

    /** @var list<int> List of users who recently viewed the story */
    public readonly array $recentViewers;

    /**
     * @readonly
     *
     * @var list<string> Regex matches, if a filter regex is present
     */
    public ?array $matches = null;

    /**
     * @readonly
     *
     * @var array<array-key, array<array-key, list{string, int}|null|string>|mixed> Regex matches, if a filter multiple match regex is present
     */
    public ?array $matchesAll = null;

    /** @internal */
    public function __construct(MTProto|Client $API, array $rawStory)
    {
        parent::__construct($API, $rawStory);
        if ($rawStory['story']['min']) {
            // TODO: cache
            $rawStory = $API->methodCallAsyncRead('stories.getStoriesByID', ['peer' => $rawStory['peer'], 'id' => [$rawStory['story']['id']]])['stories'][0];
        } else {
            $rawStory = $rawStory['story'];
        }

        $this->pinned = $rawStory['pinned'];
        $this->public = $rawStory['public'];
        $this->closeFriends = $rawStory['close_friends'];
        $this->protected = $rawStory['noforwards'];
        $this->edited = $rawStory['edited'];
        $this->contacts = $rawStory['contacts'];
        $this->selectedContacts = $rawStory['selected_contacts'];

        $this->date = $rawStory['date'];
        $this->expireDate = $rawStory['expire_date'];

        $this->media = $API->wrapMedia($rawStory['media'], $this->protected);
        $this->entities = MessageEntity::fromRawEntities($rawStory['entities'] ?? []);
        $this->privacy = array_map(RuleDestination::fromRawRule(...), $rawStory['privacy'] ?? []);

        $this->recentViewers = $rawStory['views']['recent_viewers'] ?? [];
        $this->views = $rawStory['views']['views_count'] ?? null;
        $this->reactionCount = $rawStory['views']['reactions_count'] ?? null;

        $this->caption = $rawStory['caption'] ?? '';
        //$this->mediaAreas = $rawStory['mediaAreas'] ?? null; //!
        $this->sentReaction = $rawStory['sent_reaction']['emoticon'] ?? $rawStory['sent_reaction']['document_id'] ?? null;
    }

    /**
     * Reply to the story.
     *
     * @param string       $message                Message to send
     * @param ParseMode    $parseMode              Parse mode
     * @param array|null   $replyMarkup            Keyboard information.
     * @param integer|null $scheduleDate           Schedule date.
     * @param boolean      $silent                 Whether to send the message silently, without triggering notifications.
     * @param boolean      $background             Send this message as background message
     * @param boolean      $clearDraft             Clears the draft field
     * @param boolean      $noWebpage              Set this flag to disable generation of the webpage preview
     * @param boolean      $updateStickersetsOrder Whether to move used stickersets to top
     *
     */
    public function reply(
        string $message,
        ParseMode $parseMode = ParseMode::TEXT,
        ?array $replyMarkup = null,
        ?int $scheduleDate = null,
        bool $silent = false,
        bool $background = false,
        bool $clearDraft = false,
        bool $noWebpage = false,
        bool $updateStickersetsOrder = false,
    ): Message {
        $client = $this->getClient();
        $result = $client->methodCallAsyncRead(
            'messages.sendMessage',
            [
                'peer' => $this->senderId,
                'message' => $message,
                'parse_mode' => $parseMode,
                'reply_to' => ['_' => 'inputReplyToStory', 'user_id' => $this->senderId, 'story_id' => $this->id],
                'reply_markup' => $replyMarkup,
                'schedule_date' => $scheduleDate,
                'silent' => $silent,
                'background' => $background,
                'clear_draft' => $clearDraft,
                'no_webpage' => $noWebpage,
                'update_stickersets_order' => $updateStickersetsOrder,
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
     * Delete the story.
     *
     */
    public function delete(): void
    {
        $this->getClient()->methodCallAsyncRead(
            'stories.deleteStories',
            [
                'peer' => $this->senderId,
                'id' => [$this->id],
            ]
        );
    }

    /**
     * Export story link e.g: https://t.me/username/s/storyid.
     *
     */
    public function exportLink(): string
    {
        return $this->getClient()->methodCallAsyncRead(
            'stories.exportStoryLink',
            [
                'peer' => $this->senderId,
                'id' => $this->id,
            ]
        )['link'];
    }

    /**
     * Report a story for violation of telegram’s Terms of Service.
     *
     * @param  ReportReason $reason  Why is story being reported
     * @param  string       $message Comment for report moderation
     * @return boolean
     */
    public function report(ReportReason $reason, string $message = ''): bool
    {
        return $this->getClient()->methodCallAsyncRead(
            'stories.report',
            [
                'peer' => $this->senderId,
                'id' => [$this->id],
                'reason' => ['_' => $reason->value],
                'message' => $message,
            ]
        );
    }

    /**
     * Pin a story.
     *
     */
    public function pin(): void
    {
        $this->getClient()->methodCallAsyncRead(
            'stories.togglePinned',
            [
                'peer' => $this->senderId,
                'id' => [$this->id],
                'pinned' => true,
            ]
        );
    }

    /**
     * Unpin a story.
     *
     */
    public function unpin(): void
    {
        $this->getClient()->methodCallAsyncRead(
            'stories.togglePinned',
            [
                'id' => [$this->id],
                'pinned' => false,
            ]
        );
    }

    /**
     * Mark story as read.
     *
     * @return boolean
     */
    public function view(): bool
    {
        return $this->getClient()->methodCallAsyncRead(
            'stories.incrementStoryViews',
            [
                'peer' => $this->senderId,
                'id' => [$this->id],
            ]
        );
    }

    /**
     * Reaction to story.
     *
     * @param integer|string $reaction string or int Reaction
     * @param boolean        $recent
     */
    public function addReaction(int|string $reaction, bool $recent = true): StoryReaction
    {
        $client = $this->getClient();
        $result = $client->methodCallAsyncRead(
            'stories.sendReaction',
            [
                'add_to_recent' => $recent,
                'peer' => $this->senderId,
                'story_id' => $this->id,
                'reaction' => \is_int($reaction)
                ? ['_' => 'reactionCustomEmoji', 'document_id' => $reaction]
                : ['_' => 'reactionEmoji', 'emoticon' => $reaction],
            ]
        )['updates'];
        foreach ($result as $update) {
            if ($update['_'] === 'updateSentStoryReaction') {
                return $client->wrapUpdate($update);
            }
        }
        throw new AssertionError("Could not find updateSentStoryReaction!");
    }

    /**
     * Delete reaction from story.
     *
     * @param boolean $recent string or int Reaction
     */
    public function delReaction(bool $recent = true): StoryReaction
    {
        $client = $this->getClient();
        $result = $client->methodCallAsyncRead(
            'stories.sendReaction',
            [
                'add_to_recent' => $recent,
                'peer' => $this->senderId,
                'story_id' => $this->id,
            ]
        )['updates'];
        foreach ($result as $update) {
            if ($update['_'] === 'updateSentStoryReaction') {
                return $client->wrapUpdate($update);
            }
        }
        throw new AssertionError("Could not find updateSentStoryReaction!");
    }

    protected readonly string $html;
    protected readonly string $htmlTelegram;

    /**
     * Get an HTML version of the story caption.
     *
     * @psalm-suppress InaccessibleProperty
     *
     * @param bool $allowTelegramTags Whether to allow telegram-specific tags like tg-spoiler, tg-emoji, mention links and so on...
     */
    public function getHTML(bool $allowTelegramTags = false): string
    {
        if (!$this->entities) {
            return StrTools::htmlEscape($this->caption);
        }
        if ($allowTelegramTags) {
            return $this->htmlTelegram ??= StrTools::entitiesToHtml($this->caption, $this->entities, $allowTelegramTags);
        }
        return $this->html ??= StrTools::entitiesToHtml($this->caption, $this->entities, $allowTelegramTags);
    }
}
