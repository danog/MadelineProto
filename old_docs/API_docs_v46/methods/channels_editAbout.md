---
title: channels.editAbout
description: channels.editAbout parameters, return type and example
---
## Method: channels.editAbout  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[Username, chat ID or InputChannel](../types/InputChannel.md) | Optional|
|about|[string](../types/string.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHAT_ABOUT_NOT_MODIFIED|About text has not changed|
|CHAT_ABOUT_TOO_LONG|Chat about too long|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|


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

$Bool = $MadelineProto->channels->editAbout(['channel' => InputChannel, 'about' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

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

