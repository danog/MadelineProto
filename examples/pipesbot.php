#!/usr/bin/env php
<?php
/**
 * Pipes bot.
 *
 * Copyright 2016-2019 Daniil Gentili
 * (https://daniil.it)
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

if (!\file_exists('madeline.php')) {
    \copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';


/**
 * Event handler class.
 */
class pipesbot extends \danog\MadelineProto\EventHandler
{
    const WELCOME = "This bot can create a pipeline between inline bots.
To use it, simply type an inline query with the following syntax:

```
@pipesbot text | @botname:filter | @botname2 \$
```

Example:
```
@pipesbot Hey I'm writing this using the leet filter of @filtersbot w/ @lolcatzbot | @filtersbot:eleet | @lolcatzbot \$
```

@pipesbot will:
1) Make an inline query with text \"Hey I'm writing this using the leet filter of @filtersbot w/ @lolcatzbot\" to @filtersbot
2) Take the result that has the word \"eleet\" in the title (regexes are supported; omit the selector to select the first result)
3) If it's a text message it will make an inline query to `@lolcatzbot` with the text received from the first bot
4) Fetch all results of the query to @lolcatzbot and return them to you.

Intermediate media results will be ignored.
Note that the query must be terminated by a \$.

Created by @daniilgentili using @MadelineProto (https://docs.madelineproto.xyz).";

    const SWITCH_PM = ['switch_pm' => ['_' => 'inlineBotSwitchPM', 'text' => 'FAQ', 'start_param' => 'lel']];
    const ADMIN = '@danogentili';

    /**
     * User instance of MadelineProto.
     *
     * @var \danog\MadelineProto\API
     */
    private $u;

    private function inputify(&$stuff)
    {
        $stuff['_'] = 'input'.\ucfirst($stuff['_']);

        return $stuff;
    }
    private function translatetext(&$value)
    {
        $this->inputify($value);
        if (isset($value['entities'])) {
            foreach ($value['entities'] as &$entity) {
                if ($entity['_'] === 'messageEntityMentionName') {
                    $this->inputify($entity);
                }
            }
        }
        if (isset($value['geo'])) {
            $value['geo_point'] = $this->inputify($value['geo']);
        }
    }
    private function translate(&$value, $key)
    {
        switch ($value['_']) {
        case 'botInlineResult':
            $value['_'] = 'inputBotInlineResult';
            $this->translatetext($value['send_message']);

            return $value;
        case 'botInlineMediaResult':
            if (isset($value['game'])) {
                throw new \danog\MadelineProto\Exception('Games are not supported.');
            }
            if (isset($value['photo'])) {
                $value['_'] = 'inputBotInlineResultPhoto';
            }
            if (isset($value['document'])) {
                $value['_'] = 'inputBotInlineResultDocument';
            }
            $this->translatetext($value['send_message']);

            return $value;
        }
    }
    public function onUpdateNewChannelMessage($update)
    {
        yield $this->onUpdateNewMessage($update);
    }
    public function onUpdateNewMessage($update)
    {
        try {
            if (\strpos($update['message']['message'], '/start') === 0) {
                yield $this->messages->sendMessage(['peer' => $update, 'message' => self::WELCOME, 'reply_to_msg_id' => $update['message']['id'], 'parse_mode' => 'markdown']);
            }
        } catch (\Throwable $e) {
            $this->logger($e);
        }
    }
    public function onUpdateBotInlineQuery($update)
    {
        try {
            $result = ['query_id' => $update['query_id'], 'results' => [], 'cache_time' => 0];

            if ($update['query'] === '') {
                yield $this->messages->setInlineBotResults($result + self::SWITCH_PM);
            } else {
                $result['private'] = true;
                yield $this->messages->setInlineBotResults(yield $this->processNonEmptyQuery($update['query'], $update['user_id'], $result));
            }
        } catch (\Throwable $e) {
            try {
                yield $this->messages->sendMessage(['peer' => self::ADMIN, 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
                yield $this->messages->sendMessage(['peer' => $update['user_id'], 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                $this->logger($e);
            } catch (\danog\MadelineProto\Exception $e) {
                $this->logger($e);
            }

            try {
                yield $this->messages->setInlineBotResults($result + self::SWITCH_PM);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                $this->logger($e);
            } catch (\danog\MadelineProto\Exception $e) {
                $this->logger($e);
            }
        }
    }

    private function processNonEmptyQuery($query, $user_id, $toset)
    {
        if (\preg_match('|(.*)\$\s*$|', $query, $content)) {
            $exploded = \array_map('trim', \explode('|', $content[1]));
            $query = \array_shift($exploded);

            foreach ($exploded as $current => $botSelector) {
                if (\strpos($botSelector, ':') === false) {
                    $botSelector .= ':';
                }
                list($bot, $selector) = \explode(':', $botSelector);
                if ($bot === '' || yield $this->u->getInfo($bot)['bot_api_id'] === yield $this->getSelf()['id']) {
                    return $toset + self::SWITCH_PM;
                }
                $results = yield $this->u->messages->getInlineBotResults(['bot' => $bot, 'peer' => $user_id, 'query' => $query, 'offset' => $offset]);
                if (isset($results['switch_pm'])) {
                    $toset['switch_pm'] = $results['switch_pm'];
                    return $toset;
                }
                $toset['gallery'] = $results['gallery'];
                $toset['results'] = [];

                if (\is_numeric($selector)) {
                    $toset['results'][0] = $results['results'][$selector - 1];
                } elseif ($selector === '') {
                    $toset['results'] = $results['results'];
                } else {
                    foreach ($results['results'] as $result) {
                        if (isset($result['send_message']['message']) && \preg_match('|'.$select.'|', $result['send_message']['message'])) {
                            $toset['results'][0] = $result;
                        }
                    }
                }
                if (!isset($toset['results'][0])) {
                    $toset['results'] = $results['results'];
                }
                if (\count($exploded) - 1 === $current || !isset($toset['results'][0]['send_message']['message'])) {
                    break;
                }
                $query = $toset['results'][0]['send_message']['message'];
            }
        }
        if (empty($toset['results'])) {
            $toset += self::SWITCH_PM;
        } else {
            \array_walk($toset['results'], 'translate');
        }
        return $toset;
    }

    public function setUMadelineProto($uMadelineProto)
    {
        $this->u = $uMadelineProto;
    }
}


$uMadelineProto = new \danog\MadelineProto\API('pipesuser.madeline');
$uMadelineProto->async(true);

$uMadelineProto->call(function () use ($uMadelineProto) {
    yield $uMadelineProto->echo("User login: ".PHP_EOL);
    yield $uMadelineProto->start();
});

var_dump("past here");

$MadelineProto = new \danog\MadelineProto\API('pipesbot.madeline');
$MadelineProto->async(true);
$MadelineProto->call(function () use ($MadelineProto, $uMadelineProto) {
    yield $MadelineProto->echo("Bot login: ".PHP_EOL);
    yield $MadelineProto->start();
    yield $MadelineProto->setEventHandler(PipesBot::class);
    yield $MadelineProto->getEventHandler()->setUMadelineProto($uMadelineProto);
});
