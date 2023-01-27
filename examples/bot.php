#!/usr/bin/env php
<?php declare(strict_types=1);
/**
 * Example bot.
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

use danog\MadelineProto\Db\DbArray;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\Database\Mysql;
use danog\MadelineProto\Settings\Database\Postgres;
use danog\MadelineProto\Settings\Database\Redis;

/*
 * Various ways to load MadelineProto
 */
if (file_exists('vendor/autoload.php')) {
    include 'vendor/autoload.php';
} else {
    if (!file_exists('madeline.php')) {
        copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    /**
     * @psalm-suppress MissingFile
     */
    include 'madeline.php';
}

/**
 * Event handler class.
 */
class MyEventHandler extends EventHandler
{
    /**
     * @var int|string Username or ID of bot admin
     */
    const ADMIN = "danogentili"; // Change this

    /**
     * List of properties automatically stored in database (MySQL, Postgres, redis or memory).
     *
     * @see https://docs.madelineproto.xyz/docs/DATABASE.html
     */
    protected static array $dbProperties = [
        'dataStoredOnDb' => 'array',
        'notifiedChats' => 'array',
    ];

    /**
     * @var DbArray<array-key, array>
     */
    protected DbArray $dataStoredOnDb;
    /**
     * @var DbArray<int, bool>
     */
    protected DbArray $notifiedChats;

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
    }
    /**
     * Handle updates from supergroups and channels.
     *
     * @param array $update Update
     */
    public function onUpdateNewChannelMessage(array $update): void
    {
        $this->onUpdateNewMessage($update);
    }

    /**
     * Handle updates from users.
     *
     * @param array $update Update
     */
    public function onUpdateNewMessage(array $update): void
    {
        if ($update['message']['_'] === 'messageEmpty' || $update['message']['out'] ?? false) {
            return;
        }

        $this->logger($update);

        // Chat id
        $id = $this->getId($update);

        // You can also use the built-in MadelineProto MySQL async driver!
        if (!isset($this->notifiedChats[$id])) {
            $this->notifiedChats[$id] = true;

            $this->messages->sendMessage(
                peer: $update,
                message: "This userbot is powered by [MadelineProto](https://t.me/MadelineProto)!",
                reply_to_msg_id: $update['message']['id'] ?? null,
                parse_mode: 'Markdown'
            );

            if (isset($update['message']['media'])
                && !in_array($update['message']['media']['_'], [
                    'messageMediaGame',
                    'messageMediaWebPage',
                    'messageMediaPoll'
                ])
            ) {
                $this->messages->sendMedia(
                    peer: $update,
                    message: $update['message']['message'],
                    media: $update
                );
            }
        }

        // Can be anything serializable: an array, an int, an object, ...
        $myData = [];

        if (isset($this->dataStoredOnDb['yourKey'])) {
            // Always when fetching data
            $myData = $this->dataStoredOnDb['yourKey'];
        }
        $this->dataStoredOnDb['yourKey'] = $myData + ['moreStuff' => 'yay'];

        $this->dataStoredOnDb['otherKey'] = 0;
        unset($this->dataStoredOnDb['otherKey']);

        $this->logger("Count: ".count($this->dataStoredOnDb));

        // You can even iterate over the data
        foreach ($this->dataStoredOnDb as $key => $value) {
            $this->logger($key);
            $this->logger($value);
        }
    }
}

$settings = new Settings;
$settings->getLogger()->setLevel(Logger::LEVEL_ULTRA_VERBOSE);

// You can also use Redis, MySQL or PostgreSQL
// $settings->setDb((new Redis)->setDatabase(0)->setPassword('pony'));
// $settings->setDb((new Postgres)->setDatabase('MadelineProto')->setUsername('daniil')->setPassword('pony'));
// $settings->setDb((new Mysql)->setDatabase('MadelineProto')->setUsername('daniil')->setPassword('pony'));

// Reduce boilerplate with new wrapper method.
// Also initializes error reporting, catching and reporting all errors surfacing from the event loop.
MyEventHandler::startAndLoop('uwu.madeline', $settings);
