<?php

require 'vendor/autoload.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['app_info' => ['api_id' => 6, 'api_hash' => 'eb06d4abfb49dc3eeb1aeb98ae0f581e'], 'updates' => ['handle_updates' => false]]);
$MadelineProto->start();

echo 'MadelineProto was started!';
