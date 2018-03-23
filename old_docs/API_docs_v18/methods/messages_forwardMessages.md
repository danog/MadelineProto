---
title: messages.forwardMessages
description: Forward messages
---
## Method: messages.forwardMessages  
[Back to methods index](index.md)


Forward messages

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional||
|id|Array of [CLICK ME int](../types/int.md) | Yes|The message IDs|


### Return type: [messages\_StatedMessages](../types/messages_StatedMessages.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|CHAT_ID_INVALID|The provided chat id is invalid|
|GROUPED_MEDIA_INVALID|Invalid grouped media|
|INPUT_USER_DEACTIVATED|The specified user was deleted|
|MEDIA_EMPTY|The provided media object is invalid|
|MESSAGE_ID_INVALID|The provided message id is invalid|
|MESSAGE_IDS_EMPTY|No message ids were provided|
|PEER_ID_INVALID|The provided peer id is invalid|
|RANDOM_ID_INVALID|A provided random ID is invalid|
|USER_BANNED_IN_CHANNEL|You're banned from sending messages in supergroups/channels|
|USER_IS_BLOCKED|User is blocked|
|USER_IS_BOT|Bots can't send messages to other bots|
|YOU_BLOCKED_USER|You blocked this user|
|PTS_CHANGE_EMPTY|No PTS change|
|RANDOM_ID_DUPLICATE|You provided a random ID that was already used|
|CHAT_SEND_GIFS_FORBIDDEN|You can't send gifs in this chat|
|CHAT_SEND_MEDIA_FORBIDDEN|You can't send media in this chat|
|CHAT_SEND_STICKERS_FORBIDDEN|You can't send stickers in this chat.|
|CHAT_WRITE_FORBIDDEN|You can't write in this chat|
|Timeout|A timeout occurred while fetching data from the bot|


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

// !!! This API id/API hash combination will not work !!!
// !!! You must get your own @ my.telegram.org !!!
$api_id = 0;
$api_hash = '';

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['app_info' => ['api_id' => $api_id, 'api_hash' => $api_hash]]);
$MadelineProto->start();

$messages_StatedMessages = $MadelineProto->messages->forwardMessages(['peer' => InputPeer, 'id' => [int, int], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.forwardMessages
* params - `{"peer": InputPeer, "id": [int], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.forwardMessages`

Parameters:

peer - Json encoded InputPeer

id - Json encoded  array of int




Or, if you're into Lua:

```
messages_StatedMessages = messages.forwardMessages({peer=InputPeer, id={int}, })
```

