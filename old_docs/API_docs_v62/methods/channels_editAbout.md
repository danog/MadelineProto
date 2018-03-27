---
title: channels.editAbout
description: Edit the about text of a channel/supergroup
---
## Method: channels.editAbout  
[Back to methods index](index.md)


Edit the about text of a channel/supergroup

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|The channel|
|about|[string](../types/string.md) | Yes|The new about text|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Bool = $MadelineProto->channels->editAbout(['channel' => InputChannel, 'about' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.editAbout
* params - `{"channel": InputChannel, "about": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.editAbout`

Parameters:

channel - Json encoded InputChannel

about - Json encoded string




Or, if you're into Lua:

```
Bool = channels.editAbout({channel=InputChannel, about='string', })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHAT_ABOUT_NOT_MODIFIED|About text has not changed|
|CHAT_ABOUT_TOO_LONG|Chat about too long|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|


