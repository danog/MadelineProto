---
title: getUserFull
description: Returns full information about a user by its identifier
---
## Method: getUserFull  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns full information about a user by its identifier

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|user\_id|[int](../types/int.md) | Yes|User identifier|


### Return type: [UserFull](../types/UserFull.md)

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

$UserFull = $MadelineProto->getUserFull(['user_id' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - getUserFull
* params - `{"user_id": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/getUserFull`

Parameters:

user_id - Json encoded int




Or, if you're into Lua:

```
UserFull = getUserFull({user_id=int, })
```

