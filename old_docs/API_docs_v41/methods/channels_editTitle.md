---
title: channels.editTitle
description: Edit the title of a supergroup/channel
---
## Method: channels.editTitle  
[Back to methods index](index.md)


Edit the title of a supergroup/channel

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|The channel|
|title|[string](../types/string.md) | Yes|The new channel/supergroup title|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Updates = $MadelineProto->channels->editTitle(['channel' => InputChannel, 'title' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.editTitle
* params - `{"channel": InputChannel, "title": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.editTitle`

Parameters:

channel - Json encoded InputChannel

title - Json encoded string




Or, if you're into Lua:

```
Updates = channels.editTitle({channel=InputChannel, title='string', })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|CHAT_NOT_MODIFIED|The pinned message wasn't modified|


