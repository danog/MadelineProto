---
title: channels.deleteUserHistory
description: Delete all messages of a user in a channel/supergroup
---
## Method: channels.deleteUserHistory  
[Back to methods index](index.md)


Delete all messages of a user in a channel/supergroup

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|The channel/supergroup|
|user\_id|[Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|The user|


### Return type: [messages\_AffectedHistory](../types/messages_AffectedHistory.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|


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

$messages_AffectedHistory = $MadelineProto->channels->deleteUserHistory(['channel' => InputChannel, 'user_id' => InputUser, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.deleteUserHistory`

Parameters:

channel - Json encoded InputChannel

user_id - Json encoded InputUser




Or, if you're into Lua:

```
messages_AffectedHistory = channels.deleteUserHistory({channel=InputChannel, user_id=InputUser, })
```

