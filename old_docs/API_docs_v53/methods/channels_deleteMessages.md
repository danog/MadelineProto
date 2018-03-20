---
title: channels.deleteMessages
description: channels.deleteMessages parameters, return type and example
---
## Method: channels.deleteMessages  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|
|id|Array of [CLICK ME int](../types/int.md) | Yes|


### Return type: [messages\_AffectedMessages](../types/messages_AffectedMessages.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|MESSAGE_DELETE_FORBIDDEN|You can't delete one of the messages you tried to delete, most likely because it is a service message.|


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

$messages_AffectedMessages = $MadelineProto->channels->deleteMessages(['channel' => InputChannel, 'id' => [int, int], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.deleteMessages
* params - `{"channel": InputChannel, "id": [int], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.deleteMessages`

Parameters:

channel - Json encoded InputChannel

id - Json encoded  array of int




Or, if you're into Lua:

```
messages_AffectedMessages = channels.deleteMessages({channel=InputChannel, id={int}, })
```

