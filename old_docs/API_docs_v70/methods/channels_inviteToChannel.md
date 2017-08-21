---
title: channels.inviteToChannel
description: channels.inviteToChannel parameters, return type and example
---
## Method: channels.inviteToChannel  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[InputChannel](../types/InputChannel.md) | Yes|
|users|Array of [InputUser](../types/InputUser.md) | Yes|


### Return type: [Updates](../types/Updates.md)

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

$Updates = $MadelineProto->channels->inviteToChannel(['channel' => InputChannel, 'users' => [InputUser], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.inviteToChannel
* params - `{"channel": InputChannel, "users": [InputUser], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.inviteToChannel`

Parameters:

channel - Json encoded InputChannel

users - Json encoded  array of InputUser




Or, if you're into Lua:

```
Updates = channels.inviteToChannel({channel=InputChannel, users={InputUser}, })
```

