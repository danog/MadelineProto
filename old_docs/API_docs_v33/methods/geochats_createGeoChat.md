---
title: geochats.createGeoChat
description: Create geochat
---
## Method: geochats.createGeoChat  
[Back to methods index](index.md)


Create geochat

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|title|[string](../types/string.md) | Yes|Geochat title|
|geo\_point|[InputGeoPoint](../types/InputGeoPoint.md) | Optional|Geochat location|
|address|[string](../types/string.md) | Yes|Geochat address|
|venue|[string](../types/string.md) | Yes|Geochat venue |


### Return type: [geochats\_StatedMessage](../types/geochats_StatedMessage.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$geochats_StatedMessage = $MadelineProto->geochats->createGeoChat(['title' => 'string', 'geo_point' => InputGeoPoint, 'address' => 'string', 'venue' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

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

