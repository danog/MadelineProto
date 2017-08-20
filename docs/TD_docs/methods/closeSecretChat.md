---
title: closeSecretChat
description: Closes secret chat
---
## Method: closeSecretChat  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Closes secret chat

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|secret\_chat\_id|[int](../types/int.md) | Yes|Secret chat identifier|


### Return type: [Ok](../types/Ok.md)

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

$Ok = $MadelineProto->closeSecretChat(['secret_chat_id' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - closeSecretChat
* params - `{"secret_chat_id": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/closeSecretChat`

Parameters:

secret_chat_id - Json encoded int




Or, if you're into Lua:

```
Ok = closeSecretChat({secret_chat_id=int, })
```

