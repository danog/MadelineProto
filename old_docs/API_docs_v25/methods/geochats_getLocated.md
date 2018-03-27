---
title: geochats.getLocated
description: Get nearby geochats
---
## Method: geochats.getLocated  
[Back to methods index](index.md)


Get nearby geochats

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|geo\_point|[InputGeoPoint](../types/InputGeoPoint.md) | Optional|Current location|
|radius|[int](../types/int.md) | Yes|Radius|
|limit|[int](../types/int.md) | Yes|Number of results to return|


### Return type: [geochats\_Located](../types/geochats_Located.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$geochats_Located = $MadelineProto->geochats->getLocated(['geo_point' => InputGeoPoint, 'radius' => int, 'limit' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

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

