#!/usr/bin/env php
<?php
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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\Database\Mysql;
use danog\MadelineProto\Settings\Database\Postgres;
use danog\MadelineProto\Settings\Database\Redis;

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
     * Get peer(s) where to report errors.
     *
     * @return int|string|array
     */
    public function getReportPeers()
    {
        return [self::ADMIN];
    }
    public function onStart() {
    }
    /**
     * Handle updates from users.
     *
     * @param array $update Update
     *
     * @return void
     */
    public function onUpdateNewMessage(array $update)
    {
        $this->logger($update);
    }
  public function onAny(array $update)
  {
      $this->logger($update);
  }
}

$settings = new Settings;
$settings->getLogger()->setLevel(Logger::LEVEL_ULTRA_VERBOSE);

// You can also use Redis, MySQL or PostgreSQL
// $settings->setDb((new Redis)->setDatabase(0)->setPassword('pony'));
// $settings->setDb((new Postgres)->setDatabase('MadelineProto')->setUsername('daniil')->setPassword('pony'));
// $settings->setDb((new Mysql)->setDatabase('MadelineProto')->setUsername('daniil')->setPassword('pony'));

$MadelineProto = new API('aaa.madeline', $settings);
var_dump($MadelineProto->messages->sendVote(['peer' => -1001049295266, 'msg_id' => 272778]));
/*
$a = $MadelineProto->channels->getMessages([
  'channel' => 'pony2jkanflk', 
  'id' => [6],
  'offset_id' => 4200, 
  'offset_date' => 0,
  'add_offset' => 0,
  'max_id' => 0,
  'min_id' => 0,
  
  'limit' => 100
])['messages'][0];

var_dump($MadelineProto->messages->sendVote(['peer' => 'pony2jkanflk', 'msg_id' => 6, 'options' => ['0']]));

readline();

*/















//$MadelineProto->photos->uploadProfilePhoto(['video' => '../../Video/o.mp4', 'file' => 'tests/faust.jpg', 'video_start_ts' => 1]);

/*var_dumP($MadelineProto->users->getFullUser(['id' => 'me']));
var_dumP($MadelineProto->downloadToDir($MadelineProto->getPropicInfo('me'), '/tmp'));
readline();*/
//var_dump($MadelineProto->channels->getChannels(['id' => ['https://t.me/joinchat/Bgrajz6K-aJS2Dc5HJ7dsA']]));

//var_dump($MadelineProto->messages->sendMessage(['peer' => 'danogentili', 'message' => 'lmao', 'schedule_date' => time() + 11]));
/*//var_duMP($MadelineProto->channels->getMessages(['peer' => -1001218943867, 'msg_id' => 4368]));
$id = 272211;
$id = 272319;
var_dump($MadelineProto->messages->search(['peer' => -1001049295266, 'min_date' => 0, 'max_date' => 0, 'offset_id' => 0, 'limit' => 100, 'max_id' => 0, 'min_id' => 0, 'add_offset' => 0, 'top_msg_id' => 268793]));
readline();
$s = $MadelineProto->channels->getMessages(['channel' => -1001049295266, 'id' => [$id]])['messages'][0]['media']['photo'];
var_dump($s);
readline();
$s = (string) $s[0]['bytes'];
function uploadSvgPathDecode($encoded) {
    $path = 'M';
    $len = strlen($encoded);
    for ($i = 0; $i < $len; $i++) {
      $num = ord($encoded[$i]);
      if ($num >= 128 + 64) {
        $path .= substr('AACAAAAHAAALMAAAQASTAVAAAZaacaaaahaaalmaaaqastava.az0123456789-,', $num - 128 - 64, 1);
      } else {
        if ($num >= 128) {
          $path .= ',';
        } else if ($num >= 64) {
          $path .= '-';
        }
        var_dump($num & 63);
        $path .= $num & 63;
      }
    }
    $path .= 'z';
    return $path;
  }
var_dump(uploadSvgPathDecode($s));

//var_dump($MadelineProto->stats->getBroadcastStats(['channel' => 'pony2jkanflk']));
readline();
//var_dump($MadelineProto->getFullInfo('davtur19'));
//->getMessagePublicForwards(['channel' => 'ponatqjoa', 'msg_id' => 2, 'offset_rate' => 0, 'offset_id' => 0, 'limit' => 0]));
die;*/
// Reduce boilerplate with new wrapper method.
// Also initializes error reporting, catching and reporting all errors surfacing from the event loop.
$MadelineProto->startAndLoop(MyEventHandler::class);
