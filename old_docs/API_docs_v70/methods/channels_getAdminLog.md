---
title: channels.getAdminLog
description: channels.getAdminLog parameters, return type and example
---
## Method: channels.getAdminLog  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[InputChannel](../types/InputChannel.md) | Yes|
|q|[string](../types/string.md) | Yes|
|events\_filter|[ChannelAdminLogEventsFilter](../types/ChannelAdminLogEventsFilter.md) | Optional|
|admins|Array of [InputUser](../types/InputUser.md) | Optional|
|max\_id|[long](../types/long.md) | Yes|
|min\_id|[long](../types/long.md) | Yes|
|limit|[int](../types/int.md) | Yes|


### Return type: [channels\_AdminLogResults](../types/channels_AdminLogResults.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$channels_AdminLogResults = $MadelineProto->channels->getAdminLog(['channel' => InputChannel, 'q' => 'string', 'events_filter' => ChannelAdminLogEventsFilter, 'admins' => [InputUser], 'max_id' => long, 'min_id' => long, 'limit' => int, ]);
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

