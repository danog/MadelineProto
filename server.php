<?php

require 'vendor/autoload.php';

$handler = new \danog\MadelineProto\Server(['type' => AF_INET, 'protocol' => 0, 'address' => 'localhost', 'port' => 8002, 'handler' => '\danog\MadelineProto\Server\Proxy', 'extra' => ['madeline' => new \danog\MadelineProto\API(['app_info' => ['api_id' => 6, 'api_hash' => 'eb06d4abfb49dc3eeb1aeb98ae0f581e']]), 'secret' => 'pony']]);
$handler->start();
