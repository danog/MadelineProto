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
    * create default Memory object
*/
$database = new \danog\MadelineProto\Settings\Database\Memory;

/*
    config and set auth information
*/

$database->setCleanup(true);          // set clean up befor serialize session
$database->setEnableFullPeerDb(false);// madeline proto could fetch peers full information ??
$database->setEnablePeerInfoDb(true); // enable peer basic info option

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
