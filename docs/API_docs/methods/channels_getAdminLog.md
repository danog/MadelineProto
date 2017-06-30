---
title: channels.getAdminLog
description: channels.getAdminLog parameters, return type and example
---
## Method: channels.getAdminLog  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|channel|[InputChannel](../types/InputChannel.md) | Yes|
|q|[string](../types/string.md) | Yes|
|events\_filter|[ChannelAdminLogEventsFilter](../types/ChannelAdminLogEventsFilter.md) | Optional|
|admins|Array of [InputUser](../types/InputUser.md) | Optional|
|max\_id|[long](../types/long.md) | Yes|
|min\_id|[long](../types/long.md) | Yes|
|limit|[int](../types/int.md) | Yes|


### Return type: [channels\_AdminLogResults](../types/channels_AdminLogResults.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$channels_AdminLogResults = $MadelineProto->channels->getAdminLog(['channel' => InputChannel, 'q' => string, 'events_filter' => ChannelAdminLogEventsFilter, 'admins' => [InputUser], 'max_id' => long, 'min_id' => long, 'limit' => int, ]);
```

Or, if you're into Lua:

```
channels_AdminLogResults = channels.getAdminLog({channel=InputChannel, q=string, events_filter=ChannelAdminLogEventsFilter, admins={InputUser}, max_id=long, min_id=long, limit=int, })
```

