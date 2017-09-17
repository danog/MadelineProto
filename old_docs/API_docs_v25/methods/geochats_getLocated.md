---
title: geochats.getLocated
description: geochats.getLocated parameters, return type and example
---
## Method: geochats.getLocated  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|geo\_point|[InputGeoPoint](../types/InputGeoPoint.md) | Yes|
|radius|[int](../types/int.md) | Yes|
|limit|[int](../types/int.md) | Yes|


### Return type: [geochats\_Located](../types/geochats_Located.md)

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

$geochats_Located = $MadelineProto->geochats->getLocated(['geo_point' => InputGeoPoint, 'radius' => int, 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - geochats.getLocated
* params - `{"geo_point": InputGeoPoint, "radius": int, "limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/geochats.getLocated`

Parameters:

geo_point - Json encoded InputGeoPoint

radius - Json encoded int

limit - Json encoded int




Or, if you're into Lua:

```
geochats_Located = geochats.getLocated({geo_point=InputGeoPoint, radius=int, limit=int, })
```

