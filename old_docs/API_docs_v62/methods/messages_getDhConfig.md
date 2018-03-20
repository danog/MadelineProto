---
title: messages.getDhConfig
description: messages.getDhConfig parameters, return type and example
---
## Method: messages.getDhConfig  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|version|[CLICK ME int](../types/int.md) | Yes|
|random\_length|[CLICK ME int](../types/int.md) | Yes|


### Return type: [messages\_DhConfig](../types/messages_DhConfig.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|RANDOM_LENGTH_INVALID|Random length invalid|


### MadelineProto Example:


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

$messages_DhConfig = $MadelineProto->messages->getDhConfig(['version' => int, 'random_length' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getDhConfig`

Parameters:

version - Json encoded int

random_length - Json encoded int




Or, if you're into Lua:

```
messages_DhConfig = messages.getDhConfig({version=int, random_length=int, })
```

