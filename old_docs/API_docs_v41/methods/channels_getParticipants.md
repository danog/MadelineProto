---
title: channels.getParticipants
description: channels.getParticipants parameters, return type and example
---
## Method: channels.getParticipants  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|channel|[InputChannel](../types/InputChannel.md) | Yes|
|filter|[ChannelParticipantsFilter](../types/ChannelParticipantsFilter.md) | Yes|
|offset|[int](../types/int.md) | Yes|
|limit|[int](../types/int.md) | Yes|


### Return type: [channels\_ChannelParticipants](../types/channels_ChannelParticipants.md)

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

$channels_ChannelParticipants = $MadelineProto->channels->getParticipants(['channel' => InputChannel, 'filter' => ChannelParticipantsFilter, 'offset' => int, 'limit' => int, ]);
```

Or, if you're using [PWRTelegram](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.getParticipants
* params - {"channel": InputChannel, "filter": ChannelParticipantsFilter, "offset": int, "limit": int, }

```

### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.getParticipants`

Parameters:

channel - Json encoded InputChannel
filter - Json encoded ChannelParticipantsFilter
offset - Json encoded int
limit - Json encoded int


```

Or, if you're into Lua:

```
channels_ChannelParticipants = channels.getParticipants({channel=InputChannel, filter=ChannelParticipantsFilter, offset=int, limit=int, })
```

