---
title: deleteRecentlyFoundChats
description: Clears list of recently found chats
---
## Method: deleteRecentlyFoundChats  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Clears list of recently found chats

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|


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

$Ok = $MadelineProto->deleteRecentlyFoundChats();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - deleteRecentlyFoundChats
* params - `{}`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/deleteRecentlyFoundChats`

Parameters:




Or, if you're into Lua:

```
Ok = deleteRecentlyFoundChats({})
```

