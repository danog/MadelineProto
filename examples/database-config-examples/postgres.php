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
    * create default Postgres object
*/
$database = new \danog\MadelineProto\Settings\Database\Postgres;

/*
    config and set auth information
*/

$database->setUri('127.0.0.1:5432'); // postgres db default connection inforamtion
$database->setUsername('wsudo');     // postgres db username
$database->setPassword('12345678');  // postgres db password
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
