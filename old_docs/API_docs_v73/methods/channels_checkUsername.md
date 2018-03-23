---
title: channels.checkUsername
description: Check if this username is free and can be assigned to a channel/supergroup
---
## Method: channels.checkUsername  
[Back to methods index](index.md)


Check if this username is free and can be assigned to a channel/supergroup

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|The channel/supergroup|
|username|[CLICK ME string](../types/string.md) | Yes|The new username for this channel/supergroup|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHAT_ID_INVALID|The provided chat id is invalid|
|USERNAME_INVALID|The provided username is not valid|


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

$Bool = $MadelineProto->channels->checkUsername(['channel' => InputChannel, 'username' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.checkUsername`

Parameters:

channel - Json encoded InputChannel

username - Json encoded string




Or, if you're into Lua:

```
Bool = channels.checkUsername({channel=InputChannel, username='string', })
```

