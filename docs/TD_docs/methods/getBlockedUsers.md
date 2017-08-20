---
title: getBlockedUsers
description: Returns users blocked by the current user
---
## Method: getBlockedUsers  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns users blocked by the current user

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|offset|[int](../types/int.md) | Yes|Number of users to skip in result, must be non-negative|
|limit|[int](../types/int.md) | Yes|Maximum number of users to return, can't be greater than 100|


### Return type: [Users](../types/Users.md)

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

$Users = $MadelineProto->getBlockedUsers(['offset' => int, 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - getBlockedUsers
* params - `{"offset": int, "limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/getBlockedUsers`

Parameters:

offset - Json encoded int

limit - Json encoded int




Or, if you're into Lua:

```
Users = getBlockedUsers({offset=int, limit=int, })
```

