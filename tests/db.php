<?php

declare(strict_types=1);

// Switch to another database driver, for testing

if (getenv('ACTIONS_PHAR')) {
    include 'madeline.php';
} elseif (!file_exists(__DIR__.'/../vendor/autoload.php') || getenv('ACTIONS_FORCE_PREVIOUS')) {
    echo 'You did not run composer update, using madeline.php'.PHP_EOL;
    if (!file_exists('madeline.php')) {
        copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    include 'madeline.php';
} else {
    require_once 'vendor/autoload.php';
}

use danog\MadelineProto\API;
use danog\MadelineProto\Settings\Database\Memory;
use danog\MadelineProto\Settings\Database\Mysql;
use danog\MadelineProto\Settings\Database\Postgres;
use danog\MadelineProto\Settings\Database\Redis;
use danog\MadelineProto\Settings\Database\SerializerType;

$MadelineProto = new API(__DIR__.'/../testing.madeline');

$map = [
    'memory' => new Memory,
    'mysql' => (new Mysql)->setUri('tcp://mariadb')->setUsername('MadelineProto')->setPassword('test'),
    'postgres' => (new Postgres)->setUri('tcp://postgres')->setUsername('MadelineProto')->setPassword('test'),
    'redis' => (new Redis)->setUri('redis://redis'),
];

$settings = $map[$argv[1]];

if (!$settings instanceof Memory) {
    $settings->setSerializer($argv[2] === 'igbinary' ? SerializerType::IGBINARY : SerializerType::SERIALIZE);
}

$MadelineProto->updateSettings($settings);

var_dump($MadelineProto->getFullInfo('danogentili'));
