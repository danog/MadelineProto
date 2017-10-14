---
title: destroy_session
description: destroy_session parameters, return type and example
---
## Method: destroy\_session  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|session\_id|[long](../types/long.md) | Yes|


### Return type: [DestroySessionRes](../types/DestroySessionRes.md)

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

$DestroySessionRes = $MadelineProto->destroy_session(['session_id' => long, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - destroy_session
* params - `{"session_id": long, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/destroy_session`

Parameters:

session_id - Json encoded long




Or, if you're into Lua:

```
DestroySessionRes = destroy_session({session_id=long, })
```

