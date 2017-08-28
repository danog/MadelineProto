---
title: channels.kickFromChannel
description: channels.kickFromChannel parameters, return type and example
---
## Method: channels.kickFromChannel  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[InputChannel](../types/InputChannel.md) | Yes|
|user\_id|[InputUser](../types/InputUser.md) | Yes|
|kicked|[Bool](../types/Bool.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


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

