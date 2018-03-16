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
|geo\_point|[InputGeoPoint](../types/InputGeoPoint.md) | Optional|
|address|[string](../types/string.md) | Yes|
|venue|[string](../types/string.md) | Yes|


### Return type: [geochats\_StatedMessage](../types/geochats_StatedMessage.md)

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

