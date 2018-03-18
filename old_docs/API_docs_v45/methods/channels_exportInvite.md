---
title: channels.exportInvite
description: channels.exportInvite parameters, return type and example
---
## Method: channels.exportInvite  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|


### Return type: [ExportedChatInvite](../types/ExportedChatInvite.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|INVITE_HASH_EXPIRED|The invite link has expired|


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

$ExportedChatInvite = $MadelineProto->channels->exportInvite(['channel' => InputChannel, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.exportInvite
* params - `{"channel": InputChannel, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.exportInvite`

Parameters:

channel - Json encoded InputChannel




Or, if you're into Lua:

```
ExportedChatInvite = channels.exportInvite({channel=InputChannel, })
```

