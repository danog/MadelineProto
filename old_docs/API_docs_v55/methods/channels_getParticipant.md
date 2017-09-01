---
title: channels.getParticipant
description: channels.getParticipant parameters, return type and example
---
## Method: channels.getParticipant  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[InputChannel](../types/InputChannel.md) | Yes|
|user\_id|[InputUser](../types/InputUser.md) | Yes|


### Return type: [channels\_ChannelParticipant](../types/channels_ChannelParticipant.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|USER_NOT_PARTICIPANT|You're not a member of this supergroup/channel|


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

$channels_ChannelParticipant = $MadelineProto->channels->getParticipant(['channel' => InputChannel, 'user_id' => InputUser, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.getParticipant
* params - `{"channel": InputChannel, "user_id": InputUser, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.getParticipant`

Parameters:

channel - Json encoded InputChannel

user_id - Json encoded InputUser




Or, if you're into Lua:

```
channels_ChannelParticipant = channels.getParticipant({channel=InputChannel, user_id=InputUser, })
```

