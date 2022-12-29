<?php declare(strict_types=1);
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;

/*
    * load MadelineProto in Your Project
*/

if (file_exists('vendor/autoload.php')) {
    include 'vendor/autoload.php';
} else {
    if (!file_exists('madeline.php')) {
        copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    include 'madeline.php';
}

/*
    * create default redis object
*/
$database = new \danog\MadelineProto\Settings\Database\Redis;

/*
    config and set auth information
*/

$database->setUri('127.0.0.1:6379');// redis service connection information with default port
$database->setDatabase(0);          // redis database number
$database->setPassword('wsudo');    // redis service password

/*
    create default settings object
*/

$settings = new Settings;

/*
    set and merge database settings to main settings
*/

$settings->setDb($database);

/*
    create madeline proto session
*/

$MadelineProto = new API('madeline.session', $settings);

/*
    start madeline proto workers
*/
$MadelineProto->start();
