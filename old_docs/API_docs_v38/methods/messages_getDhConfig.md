---
title: messages.getDhConfig
description: messages.getDhConfig parameters, return type and example
---
## Method: messages.getDhConfig  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|version|[int](../types/int.md) | Yes|
|random\_length|[int](../types/int.md) | Yes|


### Return type: [messages\_DhConfig](../types/messages_DhConfig.md)

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

$messages_DhConfig = $MadelineProto->messages->getDhConfig(['version' => int, 'random_length' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.getDhConfig
* params - `{"version": int, "random_length": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getDhConfig`

Parameters:

version - Json encoded int

random_length - Json encoded int




Or, if you're into Lua:

```
messages_DhConfig = messages.getDhConfig({version=int, random_length=int, })
```

