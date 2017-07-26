---
title: messages.discardEncryption
description: messages.discardEncryption parameters, return type and example
---
## Method: messages.discardEncryption  
[Back to methods index](index.md)


*You cannot use this method directly, see https://daniil.it/MadelineProto for more info on handling secret chats*




### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|chat\_id|[int](../types/int.md) | Yes|


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

$Bool = $MadelineProto->messages->discardEncryption(['chat_id' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.discardEncryption
* params - `{"chat_id": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.discardEncryption`

Parameters:

chat_id - Json encoded int



Or, if you're into Lua:

```
Bool = messages.discardEncryption({chat_id=int, })
```

