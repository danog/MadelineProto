---
title: geochats.getLocated
description: geochats.getLocated parameters, return type and example
---
## Method: geochats.getLocated  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|geo\_point|[InputGeoPoint](../types/InputGeoPoint.md) | Optional|
|radius|[int](../types/int.md) | Yes|
|limit|[int](../types/int.md) | Yes|


### Return type: [geochats\_Located](../types/geochats_Located.md)

### Can bots use this method: **YES**


### Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

// !!! This API id/API hash combination will not work !!!
// !!! You must get your own @ my.telegram.org !!!
$api_id = 0;
$api_hash = '';

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['app_info' => ['api_id' => $api_id, 'api_hash' => $api_hash]]);
$MadelineProto->start();

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

