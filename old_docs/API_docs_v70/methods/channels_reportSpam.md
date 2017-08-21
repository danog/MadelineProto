---
title: channels.reportSpam
description: channels.reportSpam parameters, return type and example
---
## Method: channels.reportSpam  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[InputChannel](../types/InputChannel.md) | Yes|
|user\_id|[InputUser](../types/InputUser.md) | Yes|
|id|Array of [int](../types/int.md) | Yes|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->channels->reportSpam(['channel' => InputChannel, 'user_id' => InputUser, 'id' => [int], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.reportSpam
* params - `{"channel": InputChannel, "user_id": InputUser, "id": [int], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.reportSpam`

Parameters:

channel - Json encoded InputChannel

user_id - Json encoded InputUser

id - Json encoded  array of int




Or, if you're into Lua:

```
Bool = channels.reportSpam({channel=InputChannel, user_id=InputUser, id={int}, })
```

