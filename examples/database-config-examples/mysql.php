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
    * create default mysql object
*/
$database = new \danog\MadelineProto\Settings\Database\Mysql;

/*
    config and set auth information
*/

$database->setUri('127.0.0.1:3306'); // local mysql service connection with default port
$database->setUsername('Wsudo');     // mysql service username
$database->setPassword('12345678');  // mysql service password
$database->setDatabase('MyBotsData');// which database madeline proto will store datas in that

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
