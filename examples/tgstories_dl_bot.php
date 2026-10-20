<?php declare(strict_types=1);

/**
 * Telegram stories downloader bot.
 *
 * Copyright 2016-2020 Daniil Gentili
 * (https://daniil.it)
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

use danog\MadelineProto\API;
use danog\MadelineProto\Broadcast\Progress;
use danog\MadelineProto\Broadcast\Status;
use danog\MadelineProto\EventHandler\Filter\FilterCommand;
use danog\MadelineProto\EventHandler\Filter\FilterRegex;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Query\ChatButtonQuery;
use danog\MadelineProto\EventHandler\SimpleFilter\FromAdmin;
use danog\MadelineProto\EventHandler\SimpleFilter\Incoming;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\SimpleEventHandler;

// MadelineProto is already loaded
if (class_exists(API::class)) {
    // Otherwise, if a stable version of MadelineProto was installed via composer, load composer autoloader
} elseif (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
} else {
    // Otherwise download an !!! alpha !!! version of MadelineProto via madeline.php
    if (!file_exists('madeline.php')) {
        copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    require_once 'madeline.php';
}

// Login as a user
$u = new API('stories_user.madeline');
if (!$u->getSelf()) {
    if (!$_GET) {
        $u->echo("Please login as a user!");
    }
    $u->start();
}
if (!$u->isSelfUser()) {
    throw new AssertionError("You must login as a user! Please delete the user.madeline folder to continue.");
}
unset($u);

final class StoriesEventHandler extends SimpleEventHandler
{
    private const HELP = "Telegram stories downloader bot, powered by @MadelineProto!\n\nUsage:\n/dlStories @danogentili - Download all the stories of a @username!\n\n[Source code](https://github.com/danog/MadelineProto/blob/v8/examples/tgstories_dl_bot.php) powered by @MadelineProto";
    // Username of the admin of the bot
    private const ADMIN = "@danogentili";

    private const PAGE_SIZE = 10;

    private API $userInstance;
    public function onStart(): void
    {
        // Login as a user
        $this->userInstance = new API('stories_user.madeline');
    }

    public function getReportPeers()
    {
        return self::ADMIN;
    }

    private int $lastLog = 0;
    /**
     * Handles updates to an in-progress broadcast.
     */
    public function onUpdateBroadcastProgress(Progress $progress): void
    {
        if (time() - $this->lastLog > 5 || $progress->status === Status::FINISHED) {
            $this->lastLog = time();
            $this->sendMessageToAdmins((string) $progress);
        }
    }

    #[FilterCommand('broadcast')]
    public function broadcastCommand(Message & FromAdmin $message): void
    {
        // We can broadcast messages to all users with /broadcast
        if (!$message->replyToMsgId) {
            $message->reply("You should reply to the message you want to broadcast.");
            return;
        }
        $this->broadcastForwardMessages(
            from_peer: $message->senderId,
            message_ids: [$message->replyToMsgId],
            drop_author: true,
            pin: true,
        );
    }

    #[FilterCommand('start')]
    public function startCmd(Incoming&Message $message): void
    {
        $message->reply(self::HELP, parseMode: ParseMode::MARKDOWN);
    }

    /**
     * Downloads all telegram stories of a user (including protected ones).
     *
     * The bot must be started via web for this command to work.
     *
     * You can also start it via CLI but you'll have to specify a download script URL in the settings: https://docs.madelineproto.xyz/docs/FILES.html#getting-a-download-link-cli-bots.
     */
    #[FilterCommand('dlStories')]
    public function dlStoriesCommand(Message $message): void
    {
        if (!$message->commandArgs) {
            $message->reply("You must specify the @username or the Telegram ID of a user to download their stories!");
            return;
        }

        [$text, $markup] = $this->renderStoriesPage($this->userInstance->getId($message->commandArgs[0]), 0);
        $message->reply($text, parseMode: ParseMode::MARKDOWN, replyMarkup: $markup, noWebpage: true);
    }

    /**
     * Shows another page of the stories of a user.
     */
    #[FilterRegex('/^stories:(-?\d+):(\d+)$/')]
    public function storiesPageQuery(ChatButtonQuery $query): void
    {
        try {
            [$text, $markup] = $this->renderStoriesPage((int) $query->matches[1], (int) $query->matches[2]);
            $query->editText($text, $markup, ParseMode::MARKDOWN, noWebpage: true);
        } catch (RPCErrorException $e) {
            // Pressing the button of the page that is already shown
            if ($e->rpc !== 'MESSAGE_NOT_MODIFIED') {
                throw $e;
            }
        } finally {
            $query->answer(cacheTime: 0);
        }
    }

    /**
     * Renders a page of the downloadable stories of a peer, newest first.
     *
     * The active stories that aren't posted to the profile come first, then the profile stories in the order of stories.getPinnedStories (the ones pinned to the top first).
     *
     * Only fetches the profile stories up to the requested page (plus one, to know whether there is a next page).
     *
     * @return array{string, ?array}
     */
    private function renderStoriesPage(int $peer, int $page): array
    {
        $needed = ($page + 1) * self::PAGE_SIZE;

        $active = $this->userInstance->stories->getPeerStories(peer: $peer)['stories']['stories'];
        // Active stories may be returned as storyItemSkipped, fetch them in full
        $skipped = array_column(array_filter($active, static fn (array $s): bool => $s['_'] === 'storyItemSkipped'), 'id');
        if ($skipped) {
            $active = array_merge(
                array_filter($active, static fn (array $s): bool => $s['_'] !== 'storyItemSkipped'),
                $this->userInstance->stories->getStoriesByID(peer: $peer, id: $skipped)['stories'],
            );
        }
        usort($active, static fn (array $a, array $b): int => $b['id'] <=> $a['id']);

        // Story ID => story, deduplicated: the active stories posted to the profile are also returned by getPinnedStories, and so are the stories pinned to the top of the profile, a second time in their place
        $seen = [];
        $stories = [];
        $add = static function (array $story) use (&$seen, &$stories): bool {
            if (isset($seen[$story['id']])) {
                return false;
            }
            $seen[$story['id']] = true;
            // Skip deleted and protected stories
            if ($story['_'] === 'storyItem' && !$story['noforwards']) {
                $stories[] = $story;
            }
            return true;
        };
        foreach ($active as $story) {
            if ($story['_'] !== 'storyItem' || !$story['pinned']) {
                $add($story);
            }
        }

        $offset = 0;
        while (count($stories) <= $needed) {
            $res = $this->userInstance->stories->getPinnedStories(peer: $peer, offset_id: $offset, limit: 100);
            $pinnedToTop = $res['pinned_to_top'] ?? [];
            $new = false;
            foreach ($res['stories'] as $story) {
                $new = $add($story) || $new;
                // The stories pinned to the top come first whatever their ID, they can't be used as offset
                if (!in_array($story['id'], $pinnedToTop, true)) {
                    $offset = $offset === 0 ? $story['id'] : min($offset, $story['id']);
                }
            }
            if (!$new) {
                break;
            }
        }

        $result = '';
        foreach (array_slice($stories, $page * self::PAGE_SIZE, self::PAGE_SIZE) as $story) {
            $cur = "- ID {$story['id']}, posted ".date(DATE_RFC850, $story['date']);
            if (isset($story['caption']) && $story['caption'] !== '') {
                $caption = mb_strlen($story['caption']) > 100 ? mb_substr($story['caption'], 0, 100).'…' : $story['caption'];
                $cur .= ', "'.self::markdownEscape($caption).'"';
            }
            $result .= "$cur; [click here to download »]({$this->userInstance->getDownloadLink($story)})\n";
        }
        if ($result === '') {
            $result = $page === 0 ? "This user has no downloadable stories!" : "No more stories!";
        } else {
            $result = "Stories, page ".($page + 1).":\n\n$result";
        }

        $buttons = [];
        if ($page > 0) {
            $buttons[] = ['_' => 'keyboardButtonCallback', 'text' => '« Previous', 'data' => "stories:$peer:".($page - 1)];
        }
        if (count($stories) > $needed) {
            $buttons[] = ['_' => 'keyboardButtonCallback', 'text' => 'Next »', 'data' => "stories:$peer:".($page + 1)];
        }
        return [
            $result,
            $buttons ? ['_' => 'replyInlineMarkup', 'rows' => [['_' => 'keyboardButtonRow', 'buttons' => $buttons]]] : null,
        ];
    }
}

$token = '<token>';

StoriesEventHandler::startAndLoopBot('stories.madeline', $token);
