---
title: geochats.createGeoChat
description: geochats.createGeoChat parameters, return type and example
---
## Method: geochats.createGeoChat  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|title|[string](../types/string.md) | Yes|
|geo\_point|[InputGeoPoint](../types/InputGeoPoint.md) | Yes|
|address|[string](../types/string.md) | Yes|
|venue|[string](../types/string.md) | Yes|


### Return type: [geochats\_StatedMessage](../types/geochats_StatedMessage.md)

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

$geochats_StatedMessage = $MadelineProto->geochats->createGeoChat(['title' => 'string', 'geo_point' => InputGeoPoint, 'address' => 'string', 'venue' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - geochats.createGeoChat
* params - `{"title": "string", "geo_point": InputGeoPoint, "address": "string", "venue": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/geochats.createGeoChat`

Parameters:

title - Json encoded string

geo_point - Json encoded InputGeoPoint

address - Json encoded string

venue - Json encoded string




Or, if you're into Lua:

```
geochats_StatedMessage = geochats.createGeoChat({title='string', geo_point=InputGeoPoint, address='string', venue='string', })
```

