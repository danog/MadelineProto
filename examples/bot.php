<?php declare(strict_types=1);
/**
 * Example bot.
 *
 * PHP 8.1.15+ or 8.2.4+ is required.
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
use danog\MadelineProto\EventHandler\Attributes\Cron;
use danog\MadelineProto\EventHandler\Attributes\Handler;
use danog\MadelineProto\EventHandler\Filter\FilterCommand;
use danog\MadelineProto\EventHandler\Filter\FilterRegex;
use danog\MadelineProto\EventHandler\Filter\FilterText;
use danog\MadelineProto\EventHandler\Filter\FilterTextCaseInsensitive;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Message\Service\DialogPhotoChanged;
use danog\MadelineProto\EventHandler\SimpleFilter\FromAdmin;
use danog\MadelineProto\EventHandler\SimpleFilter\Incoming;
use danog\MadelineProto\EventHandler\SimpleFilter\IsReply;
use danog\MadelineProto\Logger;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\Database\Mysql;
use danog\MadelineProto\Settings\Database\Postgres;
use danog\MadelineProto\Settings\Database\Redis;
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
/**
 * Event handler class.
 *
 * All properties returned by __sleep are automatically stored in the database.
 */
class MyEventHandler extends SimpleEventHandler
{
    /**
     * @var int|string Username or ID of bot admin
     */
    const ADMIN = "@me"; // !!! Change this to your username !!!

    /**
     * @var array<int, bool>
     */
    private array $notifiedChats = [];

    /**
     * Returns a list of names for properties that will be automatically saved to the session database (MySQL/postgres/redis if configured, the session file otherwise).
     */
    public function __sleep(): array
    {
        return ['notifiedChats'];
    }

    /**
     * Get peer(s) where to report errors.
     *
     * @return int|string|array
     */
    public function getReportPeers()
    {
        return [self::ADMIN];
    }
    /**
     * Initialization logic.
     */
    public function onStart(): void
    {
        $this->logger("The bot was started!");
        $this->logger($this->getFullInfo('MadelineProto'));

        $this->sendMessageToAdmins("The bot was started!");
    }

    /**
     * This cron function will be executed forever, every 60 seconds.
     */
    #[Cron(period: 60.0)]
    public function cron1(): void
    {
        $this->sendMessageToAdmins("The bot is online, current time ".date(DATE_RFC850)."!");
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

    /**
     * Handle incoming updates from users, chats and channels.
     */
    #[Handler]
    public function handleMessage(Incoming&Message $message): void
    {
        // In this example code, send the "This userbot is powered by MadelineProto!" message only once per chat.
        // Ignore all further messages coming from this chat.
        if (!isset($this->notifiedChats[$message->chatId])) {
            $this->notifiedChats[$message->chatId] = true;

            $message->reply(
                message: "This userbot is powered by [MadelineProto](https://t.me/MadelineProto)!",
                parseMode: ParseMode::MARKDOWN
            );
        }
    }

    #[FilterCommand('restart')]
    public function restartCommand(Incoming & Message & FromAdmin $message): void
    {
        // If the message is a /restart command from an admin, restart to reload changes to the event handler code.

        // Make sure to run in a bash while loop when running via CLI to allow self-restarts.
        $this->restart();
    }

    /**
     * Reposts a media file as a Telegram story.
     */
    #[FilterCommand('story')]
    public function storyCommand(Message & FromAdmin $message): void
    {
        if ($this->isSelfBot()) {
            $message->reply("Only users can post Telegram Stories!");
            return;
        }
        $media = $message->getReply(Message::class)?->media;
        if (!$media) {
            $message->reply("You should reply to a photo or video to repost it as a story!");
            return;
        }

        $this->stories->sendStory(
            media: $media,
            caption: "This story was posted using [MadelineProto](https://t.me/MadelineProto)!",
            parse_mode: ParseMode::MARKDOWN,
            privacy_rules: [['_' => 'inputPrivacyValueAllowAll']]
        );
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

        $stories = $this->stories->getUserStories(user_id: $message->commandArgs[0])['stories']['stories'];
        // Skip deleted stories
        $stories = array_filter($stories, fn (array $s): bool => $s['_'] === 'storyItem');
        // Sort by date
        usort($stories, fn ($a, $b) => $a['date'] <=> $b['date']);

        $result = "Total stories: ".count($stories)."\n\n";
        foreach ($stories as $story) {
            $cur = "- ID {$story['id']}, posted ".date(DATE_RFC850, $story['date']);
            if (isset($story['caption'])) {
                $cur .= ', "'.self::markdownEscape($story['caption']).'"';
            }
            $result .= "$cur; [click here to download »]({$this->getDownloadLink($story)})\n";
        }

        $message->reply($result, parseMode: ParseMode::MARKDOWN);
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

    #[FilterCommand('echo')]
    public function echoCmd(Message $message): void
    {
        // Contains the arguments of the command
        $args = $message->commandArgs;

        $message->reply($args[0] ?? '');
    }

    #[FilterRegex('/.*(mt?proto).*/i')]
    public function testRegex(Incoming & Message $message): void
    {
        $message->reply("Did you mean to write MadelineProto instead of ".$message->matches[1].'?');
    }

    #[FilterText('test')]
    public function pingCommand(Message $message): void
    {
        $message->reply('test reply');
    }

    #[FilterCommand('react')]
    public function reactCommand(Message&IsReply $message): void
    {
        $message->getReply(Message::class)->addReaction('👌');
    }

    #[FilterCommand('unreact')]
    public function unreactCommand(Message&IsReply $message): void
    {
        $message->getReply(Message::class)->delReaction('👌');
    }

    #[FilterTextCaseInsensitive('hi')]
    public function pingCommandCaseInsensitive(Message $message): void
    {
        $message->reply('hello');
    }

    /**
     * Called when the dialog photo of a chat or channel changes.
     */
    #[Handler]
    public function logPhotoChanged(Incoming&DialogPhotoChanged $message): void
    {
        if ($message->photo) {
            $message->reply("Nice! Here's a download link for the photo: ".$message->photo->getDownloadLink());
        }
        // The group photo was deleted
    }

    /**
     * Gets a download link for any file up to 4GB!
     *
     * The bot must be started via web for this command to work.
     *
     * You can also start it via CLI but you'll have to specify a download script URL in the settings: https://docs.madelineproto.xyz/docs/FILES.html#getting-a-download-link-cli-bots.
     */
    #[FilterCommand('dl')]
    public function downloadLink(Incoming&Message $message): void
    {
        $reply = $message->getReply(Message::class);
        if (!$reply?->media) {
            $message->reply("This command must reply to a media message!");
            return;
        }
        $reply->reply("Download link: ".$reply->media->getDownloadLink());
    }

    public static function getPluginPaths(): string|array|null
    {
        return 'plugins/';
    }
}

$settings = new Settings;
$settings->getLogger()->setLevel(Logger::LEVEL_ULTRA_VERBOSE);

// You can also use Redis, MySQL or PostgreSQL
// $settings->setDb((new Redis)->setDatabase(0)->setPassword('pony'));
// $settings->setDb((new Postgres)->setDatabase('MadelineProto')->setUsername('daniil')->setPassword('pony'));
// $settings->setDb((new Mysql)->setDatabase('MadelineProto')->setUsername('daniil')->setPassword('pony'));

// For users or bots
MyEventHandler::startAndLoop('bot.madeline', $settings);

// For bots only
// MyEventHandler::startAndLoopBot('bot.madeline', 'bot token', $settings);
