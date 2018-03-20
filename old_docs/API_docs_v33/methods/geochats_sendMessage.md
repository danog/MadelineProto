---
title: geochats.sendMessage
description: geochats.sendMessage parameters, return type and example
---
## Method: geochats.sendMessage  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[CLICK ME InputGeoChat](../types/InputGeoChat.md) | Yes|
|message|[CLICK ME string](../types/string.md) | Yes|


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

$geochats_StatedMessage = $MadelineProto->geochats->sendMessage(['peer' => InputGeoChat, 'message' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - geochats.sendMessage
* params - `{"peer": InputGeoChat, "message": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/geochats.sendMessage`

Parameters:

peer - Json encoded InputGeoChat

message - Json encoded string




Or, if you're into Lua:

```
geochats_StatedMessage = geochats.sendMessage({peer=InputGeoChat, message='string', })
```


## Return value 

If the length of the provided message is bigger than 4096, the message will be split in chunks and the method will be called multiple times, with the same parameters (except for the message), and an array of [geochats\_StatedMessage](../types/geochats_StatedMessage.md) will be returned instead.


