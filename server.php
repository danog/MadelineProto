<?php

require 'vendor/autoload.php';
$secret = 'pony';
$secret = md5('pony');
echo "Secret is $secret\n";

$handler = new \danog\MadelineProto\Server(['type' => AF_INET, 'protocol' => 0, 'address' => '0.0.0.0', 'port' => 7777, 'handler' => '\danog\MadelineProto\Server\Proxy', 'extra' => ['madeline' => new \danog\MadelineProto\API('proxy.madeline', ['app_info' => ['api_id' => 6, 'api_hash' => 'eb06d4abfb49dc3eeb1aeb98ae0f581e']]), 'secret' => hex2bin($secret), 'timeout' => 30]]);
$handler->start();
