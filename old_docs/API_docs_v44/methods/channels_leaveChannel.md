---
title: channels.leaveChannel
description: Leave a channel/supergroup
---
## Method: channels.leaveChannel  
[Back to methods index](index.md)


Leave a channel/supergroup

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|The channel/supergroup to leave|


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

$Updates = $MadelineProto->channels->leaveChannel(['channel' => InputChannel, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

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

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|USER_CREATOR|You can't leave this channel, because you're its creator|
|USER_NOT_PARTICIPANT|You're not a member of this supergroup/channel|
|CHANNEL_PUBLIC_GROUP_NA|channel/supergroup not available|


