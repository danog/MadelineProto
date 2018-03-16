---
title: messages.getBotCallbackAnswer
description: messages.getBotCallbackAnswer parameters, return type and example
---
## Method: messages.getBotCallbackAnswer  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|game|[Bool](../types/Bool.md) | Optional|
|peer|[InputPeer](../types/InputPeer.md) | Optional|
|msg\_id|[int](../types/int.md) | Yes|
|data|[bytes](../types/bytes.md) | Optional|


### Return type: [messages\_BotCallbackAnswer](../types/messages_BotCallbackAnswer.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|DATA_INVALID|Encrypted data invalid|
|MESSAGE_ID_INVALID|The provided message id is invalid|
|PEER_ID_INVALID|The provided peer id is invalid|
|Timeout|A timeout occurred while fetching data from the bot|


### Example:


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

$messages_BotCallbackAnswer = $MadelineProto->messages->getBotCallbackAnswer(['game' => Bool, 'peer' => InputPeer, 'msg_id' => int, 'data' => 'bytes', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getBotCallbackAnswer`

Parameters:

game - Json encoded Bool

peer - Json encoded InputPeer

msg_id - Json encoded int

data - Json encoded bytes




Or, if you're into Lua:

```
messages_BotCallbackAnswer = messages.getBotCallbackAnswer({game=Bool, peer=InputPeer, msg_id=int, data='bytes', })
```

