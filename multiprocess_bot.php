#!/usr/bin/env php
<?php
if(!function_exists("readline")) {
    function readline($prompt = null){
        if($prompt){
            echo $prompt;
        }
        $fp = fopen("php://stdin","r");
        $line = rtrim(fgets($fp, 1024));
        return $line;
    }
}

set_time_limit(0);
set_include_path(get_include_path().':'.realpath(dirname(__FILE__).'/MadelineProto/'));

require 'vendor/autoload.php';
$settings = ['app_info' => ['api_id' => 6, 'api_hash' => 'eb06d4abfb49dc3eeb1aeb98ae0f581e'], 'connection_settings' => ['all' => ['protocol' => 'tcp_full']]];

try {
    $MadelineProto = new \danog\MadelineProto\API('bot.madeline');
} catch (\danog\MadelineProto\Exception $e) {
    $MadelineProto = new \danog\MadelineProto\API($settings);
    $authorization = $MadelineProto->bot_login(readline('Enter bot token: '));
}
$MadelineProto->session = 'bot.madeline';
$offset = 0;
while (true) {
    $updates = $MadelineProto->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]);
    foreach ($updates as $update) {
        $offset = $update['update_id'] + 1;
        $pid = pcntl_fork();
        if ($pid === -1) {
            die('Forking failed'.PHP_EOL);
        } else if ($pid) {
            echo "Created child with PID $pid".PHP_EOL;
        } else {
            switch ($update['update']['_']) {
                case 'updateNewMessage':
                case 'updateNewChannelMessage':
                    if (isset($update['update']['message']['out']) && $update['update']['message']['out']) {
                        continue;
                    }
                    $res = 'Hi!';

                    try {
                        $MadelineProto->messages->sendMessage(['peer' => $update['update']['_'] === 'updateNewMessage' ? $update['update']['message']['from_id'] : $update['update']['message']['to_id'], 'message' => $res, 'reply_to_msg_id' => $update['update']['message']['id'], 'entities' => [['_' => 'messageEntityPre', 'offset' => 0, 'length' => strlen($res), 'language' => 'json']]]);
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                    }

                    try {
                        if (isset($update['update']['message']['media']) && ($update['update']['message']['media']['_'] == 'messageMediaPhoto' || $update['update']['message']['media']['_'] == 'messageMediaDocument')) {
                            $file = $MadelineProto->download_to_dir($update['update']['message']['media'], '/usr/share/nginx/html/filesbot');
                            $MadelineProto->messages->sendMessage(['peer' => isset($update['update']['message']['from_id']) ? $update['update']['message']['from_id'] : $update['update']['message']['to_id'], 'message' => str_replace('/usr/share/nginx/html', 'http://80.82.79.226', $file), 'reply_to_msg_id' => $update['update']['message']['id'], 'entities' => [['_' => 'messageEntityPre', 'offset' => 0, 'length' => strlen($res), 'language' => 'json']]]);
                        }
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                    }
            }
            die;
        }
    }
}
