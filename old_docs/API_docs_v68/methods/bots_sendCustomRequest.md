---
title: bots.sendCustomRequest
description: bots.sendCustomRequest parameters, return type and example
---
## Method: bots.sendCustomRequest  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|custom\_method|[CLICK ME string](../types/string.md) | Yes|
|params|[CLICK ME DataJSON](../types/DataJSON.md) | Yes|


### Return type: [DataJSON](../types/DataJSON.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|USER_BOT_INVALID|This method can only be called by a bot|


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

$DataJSON = $MadelineProto->bots->sendCustomRequest(['custom_method' => 'string', 'params' => DataJSON, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - bots.sendCustomRequest
* params - `{"custom_method": "string", "params": DataJSON, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/bots.sendCustomRequest`

Parameters:

custom_method - Json encoded string

params - Json encoded DataJSON




Or, if you're into Lua:

```
DataJSON = bots.sendCustomRequest({custom_method='string', params=DataJSON, })
```

