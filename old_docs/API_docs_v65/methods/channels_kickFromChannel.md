---
title: channels.kickFromChannel
description: channels.kickFromChannel parameters, return type and example
---
## Method: channels.kickFromChannel  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[Username, chat ID or InputChannel](../types/InputChannel.md) | Optional|
|user\_id|[Username, chat ID or InputUser](../types/InputUser.md) | Optional|
|kicked|[Bool](../types/Bool.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


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

$Updates = $MadelineProto->channels->kickFromChannel(['channel' => InputChannel, 'user_id' => InputUser, 'kicked' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.kickFromChannel
* params - `{"channel": InputChannel, "user_id": InputUser, "kicked": Bool, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.kickFromChannel`

Parameters:

channel - Json encoded InputChannel

user_id - Json encoded InputUser

kicked - Json encoded Bool




Or, if you're into Lua:

```
Updates = channels.kickFromChannel({channel=InputChannel, user_id=InputUser, kicked=Bool, })
```

