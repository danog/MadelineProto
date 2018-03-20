---
title: channels.getAdminLog
description: channels.getAdminLog parameters, return type and example
---
## Method: channels.getAdminLog  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|
|q|[CLICK ME string](../types/string.md) | Yes|
|events\_filter|[CLICK ME ChannelAdminLogEventsFilter](../types/ChannelAdminLogEventsFilter.md) | Optional|
|admins|Array of [Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|
|max\_id|[CLICK ME long](../types/long.md) | Yes|
|min\_id|[CLICK ME long](../types/long.md) | Yes|
|limit|[CLICK ME int](../types/int.md) | Yes|


### Return type: [channels\_AdminLogResults](../types/channels_AdminLogResults.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
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

$channels_AdminLogResults = $MadelineProto->channels->getAdminLog(['channel' => InputChannel, 'q' => 'string', 'events_filter' => ChannelAdminLogEventsFilter, 'admins' => [InputUser, InputUser], 'max_id' => long, 'min_id' => long, 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.getAdminLog`

Parameters:

channel - Json encoded InputChannel

q - Json encoded string

events_filter - Json encoded ChannelAdminLogEventsFilter

admins - Json encoded  array of InputUser

max_id - Json encoded long

min_id - Json encoded long

limit - Json encoded int




Or, if you're into Lua:

```
channels_AdminLogResults = channels.getAdminLog({channel=InputChannel, q='string', events_filter=ChannelAdminLogEventsFilter, admins={InputUser}, max_id=long, min_id=long, limit=int, })
```

