---
title: geochats.sendMedia
description: geochats.sendMedia parameters, return type and example
---
## Method: geochats.sendMedia  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputGeoChat](../types/InputGeoChat.md) | Yes|
|media|[InputMedia](../types/InputMedia.md) | Optional|


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

$geochats_StatedMessage = $MadelineProto->geochats->sendMedia(['peer' => InputGeoChat, 'media' => InputMedia, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - geochats.sendMedia
* params - `{"peer": InputGeoChat, "media": InputMedia, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/geochats.sendMedia`

Parameters:

peer - Json encoded InputGeoChat

media - Json encoded InputMedia




Or, if you're into Lua:

```
geochats_StatedMessage = geochats.sendMedia({peer=InputGeoChat, media=InputMedia, })
```

