---
title: channels.toggleInvites
description: Allow or disallow any user to invite users to this channel/supergroup
---
## Method: channels.toggleInvites  
[Back to methods index](index.md)


Allow or disallow any user to invite users to this channel/supergroup

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|The channel/supergroup|
|enabled|[Bool](../types/Bool.md) | Yes|Allow or disallow|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Updates = $MadelineProto->channels->toggleInvites(['channel' => InputChannel, 'enabled' => Bool, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.toggleInvites`

Parameters:

channel - Json encoded InputChannel

enabled - Json encoded Bool




Or, if you're into Lua:

```
Updates = channels.toggleInvites({channel=InputChannel, enabled=Bool, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|CHAT_NOT_MODIFIED|The pinned message wasn't modified|


