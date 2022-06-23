<?php


// Switch to another database driver, for testing

use danog\MadelineProto\Settings\Database\Memory;
use danog\MadelineProto\Settings\Database\Mysql;
use danog\MadelineProto\Settings\Database\Postgres;
use danog\MadelineProto\Settings\Database\Redis;

$MadelineProto = new \danog\MadelineProto\API(__DIR__.'/../testing.madeline');

$MadelineProto->updateSettings(match ($argv[1]) {
    'memory' => new Memory,
    'mysql' => (new Mysql)->setUri('tcp://mariadb')->setUsername('MadelineProto')->setPassword('test'),
    'postgres' => (new Postgres)->setUri('tcp://postgres')->setUsername('MadelineProto')->setPassword('test'),
    'redis' => (new Redis)->setUri('tcp://redis'),
});

$MadelineProto->contacts->resolveUsername(username: 'danogentili');
$MadelineProto->getFullInfo('danogentili');