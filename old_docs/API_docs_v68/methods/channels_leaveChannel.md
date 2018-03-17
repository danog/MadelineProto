---
title: channels.leaveChannel
description: channels.leaveChannel parameters, return type and example
---
## Method: channels.leaveChannel  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[Username, chat ID or InputChannel](../types/InputChannel.md) | Optional|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|USER_CREATOR|You can't leave this channel, because you're its creator|
|USER_NOT_PARTICIPANT|You're not a member of this supergroup/channel|
|CHANNEL_PUBLIC_GROUP_NA|channel/supergroup not available|


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

$Updates = $MadelineProto->channels->leaveChannel(['channel' => InputChannel, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.leaveChannel
* params - `{"channel": InputChannel, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.leaveChannel`

Parameters:

channel - Json encoded InputChannel




Or, if you're into Lua:

```
Updates = channels.leaveChannel({channel=InputChannel, })
```

