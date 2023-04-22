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

// If a stable version of MadelineProto was installed via composer, load composer autoloader
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
} else {
    // Otherwise download an alpha version of MadelineProto via madeline.php
    if (!file_exists('madeline.php')) {
        copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    require_once 'madeline.php';
}
/**
 * Event handler class.
 */
class SecretHandler extends EventHandler
{
    /**
     * @var int|string Username or ID of bot admin
     */
    const ADMIN = "danogentili"; // !!! Change this to your username !!!

    /**
     * List of properties automatically stored in database (MySQL, Postgres, redis or memory).
     *
     * Note that **all** properties will be stored in the database, regardless of whether they're specified here.
     * The only difference is that properties *not* specified in this array will also always have a full copy in RAM.
     *
     * Also, properties specified in this array are NOT thread-safe, meaning you should also use a synchronization primitive
     * from https://github.com/amphp/sync/ to use them in a thread-safe manner.
     *
     * @see https://docs.madelineproto.xyz/docs/DATABASE.html
     */
    protected static array $dbProperties = [
        'dataStoredOnDb' => 'array',
    ];

    /**
     * @var DbArray<array-key, array>
     */
    protected DbArray $dataStoredOnDb;
    /**
     * This property is also saved in the db, but it's also always kept in memory, unlike $dataStoredInDb which is exclusively stored in the db.
     * @var array<int, bool>
     */
    protected array $notifiedChats = [];

    private int $adminId;

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
        $this->adminId = $this->getId(self::ADMIN);

        $this->messages->sendMessage(
            peer: self::ADMIN,
            message: "The bot was started!"
        );
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

        // Chat ID
        $id = $this->getId($update);

        // Sender ID, not always present
        $from_id = isset($update['message']['from_id'])
            ? $this->getId($update['message']['from_id'])
            : null;

        // In this example code, send the "This userbot is powered by MadelineProto!" message only once per chat.
        // Ignore all further messages coming from this chat.
        if (!isset($this->notifiedChats[$id])) {
            $this->notifiedChats[$id] = true;

            $this->messages->sendMessage(
                peer: $update,
                message: "This userbot is powered by [MadelineProto](https://t.me/MadelineProto)!",
                reply_to_msg_id: $update['message']['id'] ?? null,
                parse_mode: 'Markdown'
            );
        }

        // If the message is a /restart command from an admin, restart to reload changes to the event handler code.
        if (($update['message']['message'] ?? '') === '/restart'
            && $from_id === $this->adminId
        ) {
            // Make sure to run in a bash while loop when running via CLI to allow self-restarts.
            $this->restart();
        }

        // Remove the following example code when running your bot

        // Test MadelineProto's built-in database driver, which automatically maps to MySQL/PostgreSQL/Redis
        // properties mentioned in the MyEventHandler::$dbProperties property!

        // Note that **all** properties will be stored in the database, regardless of whether they're specified in $dbProperties
        // The only difference is that properties *not* specified in $dbProperties will also always have a full copy in RAM.
        //
        // Also, properties specified in $dbProperties are NOT thread-safe, meaning you should also use a synchronization primitive
        // from https://github.com/amphp/sync/ to use them in a thread-safe manner.
        //
        // Can be anything serializable: an array, an int, an object, ...
        $myData = [];

        if (isset($this->dataStoredOnDb['k1'])) {
            $myData = $this->dataStoredOnDb['k1'];
        }
        $this->dataStoredOnDb['k1'] = $myData + ['moreStuff' => 'yay'];

        $this->dataStoredOnDb['k2'] = 0;
        unset($this->dataStoredOnDb['k2']);

        $this->logger("Count: ".count($this->dataStoredOnDb));

        // You can even iterate over the data
        foreach ($this->dataStoredOnDb as $key => $value) {
            $this->logger($key);
            $this->logger($value);
        }
    }

    // 100+ other types of onUpdate... method types are available, see https://docs.madelineproto.xyz/API_docs/types/Update.html for the full list.

    // You can also use onAny to catch all update types (only for debugging)
}

$settings = new Settings;
$settings->getLogger()->setLevel(Logger::LEVEL_ULTRA_VERBOSE);

// You can also use Redis, MySQL or PostgreSQL
// $settings->setDb((new Redis)->setDatabase(0)->setPassword('pony'));
// $settings->setDb((new Postgres)->setDatabase('MadelineProto')->setUsername('daniil')->setPassword('pony'));
// $settings->setDb((new Mysql)->setDatabase('MadelineProto')->setUsername('daniil')->setPassword('pony'));

// For users or bots
SecretHandler::startAndLoop('bot.madeline', $settings);

// For bots only
// SecretHandler::startAndLoopBot('bot.madeline', 'bot token', $settings);
