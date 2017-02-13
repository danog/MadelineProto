---
title: channels.getParticipants
description: channels.getParticipants parameters, return type and example
---
## Method: channels.getParticipants  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|channel|[InputChannel](../types/InputChannel.md) | Required|
|filter|[ChannelParticipantsFilter](../types/ChannelParticipantsFilter.md) | Required|
|offset|[int](../types/int.md) | Required|
|limit|[int](../types/int.md) | Required|


### Return type: [channels\_ChannelParticipants](../types/channels_ChannelParticipants.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
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
