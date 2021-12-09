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
$database = new \danog\MadelineProto\Settings\Database\Memory;

/* 
    config and set auth information
*/

$database->setCleanup(true);
$database->setEnableFullPeerDb(false);
$database->setEnablePeerInfoDb(true);

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
