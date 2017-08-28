---
title: geochats.getRecents
description: geochats.getRecents parameters, return type and example
---
## Method: geochats.getRecents  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|offset|[int](../types/int.md) | Yes|
|limit|[int](../types/int.md) | Yes|


### Return type: [geochats\_Messages](../types/geochats_Messages.md)

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

$geochats_Messages = $MadelineProto->geochats->getRecents(['offset' => int, 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - geochats.getRecents
* params - `{"offset": int, "limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/geochats.getRecents`

Parameters:

offset - Json encoded int

limit - Json encoded int




Or, if you're into Lua:

```
geochats_Messages = geochats.getRecents({offset=int, limit=int, })
```

