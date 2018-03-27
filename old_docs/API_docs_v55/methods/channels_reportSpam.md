---
title: channels.reportSpam
description: Report a message in a supergroup/channel for spam
---
## Method: channels.reportSpam  
[Back to methods index](index.md)


Report a message in a supergroup/channel for spam

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|The channel|
|user\_id|[Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|The user that sent the messages|
|id|Array of [int](../types/int.md) | Yes|The IDs of messages to report|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Bool = $MadelineProto->channels->reportSpam(['channel' => InputChannel, 'user_id' => InputUser, 'id' => [int, int], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.reportSpam`

Parameters:

channel - Json encoded InputChannel

user_id - Json encoded InputUser

id - Json encoded  array of int




Or, if you're into Lua:

```
Bool = channels.reportSpam({channel=InputChannel, user_id=InputUser, id={int}, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|INPUT_USER_DEACTIVATED|The specified user was deleted|


