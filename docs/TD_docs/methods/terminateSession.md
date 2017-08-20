---
title: terminateSession
description: Terminates another session of logged in user
---
## Method: terminateSession  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Terminates another session of logged in user

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|session\_id|[long](../types/long.md) | Yes|Session identifier|


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

$Ok = $MadelineProto->terminateSession(['session_id' => long, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - terminateSession
* params - `{"session_id": long, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/terminateSession`

Parameters:

session_id - Json encoded long




Or, if you're into Lua:

```
Ok = terminateSession({session_id=long, })
```

