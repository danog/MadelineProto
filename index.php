<?php
require 'vendor/autoload.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['app_info' => ['api_id' => 6, 'api_hash' => 'eb06d4abfb49dc3eeb1aeb98ae0f581e'], 'updates' => ['handle_updates' => false]]);
$me = $MadelineProto->start();

echo 'MadelineProto was started!';

if (!$me['bot']) {
    $MadelineProto->channels->joinChannel(['channel' => '@MadelineProto']);
    $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => 'Testing MadelineProto from a browser :D']);
    try {
        $MadelineProto->messages->importChatInvite(['hash' => 'https://t.me/joinchat/Bgrajz6K-aJKu0IpGsLpBg']);
    } catch (\danog\MadelineProto\RPCErrorException $e) {}
    $MadelineProto->messages->sendMessage(['peer' => 'https://t.me/joinchat/Bgrajz6K-aJKu0IpGsLpBg', 'message' => 'Testing MadelineProto!']);
}