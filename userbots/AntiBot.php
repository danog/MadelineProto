<?php
/**
 * Created by PhpStorm.
 * User: hadi (t.me/haznet)
 * Date: 14/03/2019
 * Time: 07:52 PM
 * description : this bot, only  Delete Bot Message or Ban are
 */

$REQ_INFO = ['admin' => '-- YOUR ID --']; //CHANGE CHANGE CHANGE CHANGE CHANGE CHANGE CHANGE 

if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
    echo 'You did not run composer update, using madeline.php'.PHP_EOL;
    if (!file_exists('madeline.php')) {
        copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    include 'madeline.php';
} else {
    require_once 'vendor/autoload.php';
}

$MadelineProto = new \danog\MadelineProto\API('bot.madeline');
$MadelineProto->start();
$offset = 0;
while (true) {
    $updates = $MadelineProto->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]);
    \danog\MadelineProto\Logger::log($updates);
    foreach ($updates as $update) {
        $offset = $update['update_id'] + 1;
        switch ($update['update']['_']) {
            case 'updateNewMessage':
            case 'updateNewChannelMessage':
                if (isset($update['update']['message']['out']) && $update['update']['message']['out']) {
                    continue;
                }

                try {
                    if (isset($update['update']['message']['to_id']['channel_id']) and isset($update['update']['message']['from_id'])) {
                        $me = $MadelineProto->get_self()['id'];
                        $channelParticipantsAdmins = ['_' => 'channelParticipantsAdmins'];
                        $channels_ChannelParticipants = $MadelineProto->channels->getParticipants(['channel' => '-' . $update['update']['message']['to_id']['channel_id'], 'filter' => $channelParticipantsAdmins, 'offset' => 0, 'limit' => 0,]);

                        foreach ($channels_ChannelParticipants['participants'] as $val) {
                            if ($val['user_id'] == $me) {
                                $admin = true;
                                exit;
                            }
                        }

                        if ($admin == true) {
                            $Chat85 = $MadelineProto->get_full_info($update['update']['message']['from_id']);
                            if ($Chat85['User']['bot'] == true) {
                                try {
                                    $MadelineProto->channels->deleteMessages(['channel' => '-' . $update['update']['message']['to_id']['channel_id'], 'id' => [$update['update']['message']['id']]]);
                                    $chatBannedRights = ['_' => 'chatBannedRights', 'view_messages' => true, 'send_messages' => false, 'send_media' => false, 'send_stickers' => false, 'send_gifs' => false, 'send_games' => false, 'send_inline' => false, 'embed_links' => false, 'send_polls' => false, 'change_info' => false, 'invite_users' => false, 'pin_messages' => false, 'until_date' => 858585858585858];
                                    $MadelineProto->channels->editBanned(['channel' => '-' . $update['update']['message']['to_id']['channel_id'], 'user_id' => $update['update']['message']['from_id'], 'banned_rights' => $chatBannedRights,]);
                                } catch (\danog\MadelineProto\RPCErrorException $e) {
                                    $MadelineProto->messages->sendMessage(['peer' => $REQ_INFO['admin'], 'message' => $e->getCode() . ': ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString()]);
                                }

                            }
                        }
                    }

                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    $MadelineProto->messages->sendMessage(['peer' => $REQ_INFO['admin'], 'message' => $e->getCode() . ': ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString()]);
                }
        }
    }
}
