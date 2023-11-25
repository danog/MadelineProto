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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

use danog\MadelineProto\API;
use danog\MadelineProto\Broadcast\Progress;
use danog\MadelineProto\Broadcast\Status;
use danog\MadelineProto\EventHandler\Filter\FilterCommand;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\SimpleFilter\FromAdmin;
use danog\MadelineProto\EventHandler\SimpleFilter\Incoming;
use danog\MadelineProto\ParseMode;
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

    private API $userInstance;
    public function onStart(): void
    {
        // Login as a user
        $this->echo("Please login as a user!");
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

        $stories = $this->userInstance->stories->getPeerStories(peer: $message->commandArgs[0])['stories']['stories'];
        $last = null;
        do {
            $res = $this->userInstance->stories->getPinnedStories(peer: $message->commandArgs[0], offset_id: $last)['stories'];
            $last = $res ? end($res)['id'] : null;
            $stories = array_merge($res, $stories);
        } while ($last);
        // Skip deleted stories
        $stories = array_filter($stories, static fn (array $s): bool => $s['_'] === 'storyItem');
        // Skip protected stories
        $stories = array_filter($stories, static fn (array $s): bool => !$s['noforwards']);
        // Sort by date
        usort($stories, static fn ($a, $b) => $a['date'] <=> $b['date']);

        $message = $message->reply("Total stories: ".count($stories));
        foreach (array_chunk($stories, 10) as $sub) {
            $result = '';
            foreach ($sub as $story) {
                $cur = "- ID {$story['id']}, posted ".date(DATE_RFC850, $story['date']);
                if (isset($story['caption'])) {
                    $cur .= ', "'.self::markdownEscape($story['caption']).'"';
                }
                $result .= "$cur; [click here to download »]({$this->userInstance->getDownloadLink($story)})\n";
            }
            $message = $message->reply($result, parseMode: ParseMode::MARKDOWN);
        }
    }
}

$token = '<token>';

StoriesEventHandler::startAndLoopBot('stories.madeline', $token);
