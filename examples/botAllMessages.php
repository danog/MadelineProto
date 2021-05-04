#!/usr/bin/env php
<?php
/**
 * Get all messages in multiple threads.
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
 * @author    Alexander Panlratov <alexander@i-c-a.su>
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

/*
 * Various ways to load MadelineProto
 */
if (\file_exists('vendor/autoload.php')) {
    include 'vendor/autoload.php';
} else {
    if (!\file_exists('madeline.php')) {
        \copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    /**
     * @psalm-suppress MissingFile
     */
    include 'madeline.php';
}

$MadelineProto = new \danog\MadelineProto\API('bot.madeline');

$MadelineProto->async(true);
$MadelineProto->loop(static function () use ($MadelineProto) {
    yield $MadelineProto->start();

    $lastMessageId = 0;
    $threads = 5;
    $step = 20;
    $totalMessages = 0;
    $start = \microtime(true);
    while (true) {
        $promises = [];
        for ($i = 0; $i < $threads; $i++) {
            $promises[] = $MadelineProto->messages->getMessages(['id' => \range($lastMessageId+1, $lastMessageId+$step)]);
            $lastMessageId +=$step;
        }
        $results = yield \Amp\Promise\all($promises);
        foreach ($results as $result) {
            foreach ($result['messages'] as $message) {
                if ($message['_'] === 'messageEmpty') {
                    break 3;
                }
                $totalMessages++;
                yield $MadelineProto->echo("\rTotal messages processed: {$totalMessages}");
            }
        }
    };
    $time = \microtime(true)-$start;
    yield $MadelineProto->echo("\nTime: {$time}\n");
});
