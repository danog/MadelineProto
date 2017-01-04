<?php
require 'vendor/autoload.php';
$settings = [];
$MadelineProto = \danog\MadelineProto\Serialization::deserialize('bot.madeline');

if (file_exists('token.php') && $MadelineProto === false) {
    include_once 'token.php';
    $MadelineProto = new \danog\MadelineProto\API($settings);
    $authorization = $MadelineProto->bot_login($token);
    \danog\MadelineProto\Logger::log($authorization);
}
$offset = 0;
while (true) {
    $updates = $MadelineProto->API->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]); // Just like in the bot API, you can specify an offset, a limit and a timeout
    foreach ($updates as $update) {
        $offset = $update['update_id'] + 1; // Just like in the bot API, the offset must be set to the last update_id
        var_dump($update);
        switch ($update['update']['_']) {
            case 'updateNewMessage':
                if ($update['update']['message']['out']) continue;
                $res = json_encode($update, JSON_PRETTY_PRINT);
                if ($res == '') $res =var_export($update, true);
                try { $MadelineProto->messages->sendMessage(['peer' => $update['update']['message']['from_id'], 'message' => $res, 'reply_to_msg_id' => $update['update']['message']['id'], 'entities' => [['_' => 'messageEntityPre', 'offset' => 0, 'length' => strlen($res), 'language' => 'json', ]]]); } catch (\danog\MadelineProto\RPCErrorException $e) { $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]); }
        }
    }
    echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('bot.madeline', $MadelineProto).' bytes'.PHP_EOL;
}

