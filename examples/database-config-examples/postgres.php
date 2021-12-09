<?php
use danog\MadelineProto\Settings;
use danog\MadelineProto\API;

/* 
    * load MadelineProto in Your Project
*/


if (\file_exists('vendor/autoload.php')) {
    include 'vendor/autoload.php';
} else {
    if (!\file_exists('madeline.php')) {
        \copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    include 'madeline.php';
}

/* 
    * create default mysql object
*/
$database = new \danog\MadelineProto\Settings\Database\Postgres;

/* 
    config and set auth information
*/

$database->setUri('127.0.0.1:5432');
$database->setUsername('wsudo');
$database->setPassword('12345678');
$database->setDatabase('MyBotsData');

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

$MadelineProto = new API('madeline.session' , $settings);

/* 
    start madeline proto workers
*/
$MadelineProto->start();
