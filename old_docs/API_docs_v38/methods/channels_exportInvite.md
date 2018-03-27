---
title: channels.exportInvite
description: Export the invite link of a channel
---
## Method: channels.exportInvite  
[Back to methods index](index.md)


Export the invite link of a channel

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|The channel|


### Return type: [ExportedChatInvite](../types/ExportedChatInvite.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$ExportedChatInvite = $MadelineProto->channels->exportInvite(['channel' => InputChannel, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

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

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|INVITE_HASH_EXPIRED|The invite link has expired|


