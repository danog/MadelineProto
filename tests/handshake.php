<?php declare(strict_types=1);

use danog\MadelineProto\API;
use danog\MadelineProto\Settings\AppInfo;

require 'vendor/autoload.php';

$settings = new AppInfo;

// Published test API ID from https://github.com/DrKLO/Telegram/blob/master/TMessagesProj/src/main/java/org/telegram/messenger/BuildVars.java#L29
// Can only be used for a handshake, login won't work, get your own API ID/hash @ my.telegram.org to fix.
$settings->setApiId(4)->setApiHash('014b35b6184100b085b0d0572f9b5103');

$test = new API('/tmp/handshake_'.random_int(0, PHP_INT_MAX).'.madeline', $settings);
var_dump($test->help->getConfig([], ['datacenter' => 4]));
$test->logout();
