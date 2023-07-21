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
use danog\MadelineProto\EventHandler\Filter\FilterCommand;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\SimpleFilter\Incoming;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\SimpleEventHandler;

require 'vendor/autoload.php';

final class StoriesEventHandler extends SimpleEventHandler
{
    private const HELP = "Telegram stories downloader bot, powered by @MadelineProto!\n\nUsage:\n- /dlStories @username - Download all the stories of a username!";

    private API $userInstance;
    public function onStart(): void
    {
        // Login as a user
        $this->userInstance = new API('stories_user.madeline');
        $this->userInstance->start();
        if (!$this->userInstance->isSelfUser()) {
            throw new AssertionError("You must login as a user! Please delete the user.madeline folder to continue.");
        }
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

        $stories = $this->userInstance->stories->getUserStories(user_id: $message->commandArgs[0])['stories']['stories'];
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
            $result .= "$cur; [click here to download »]({$this->userInstance->getDownloadLink($story)})\n";
        }

        $message->reply($result, parseMode: ParseMode::MARKDOWN);
    }
}

$token = '<token>';

StoriesEventHandler::startAndLoopBot('stories.madeline', $token);
