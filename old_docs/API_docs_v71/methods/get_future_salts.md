---
title: get_future_salts
description: Get future salts
---
## Method: get\_future\_salts  
[Back to methods index](index.md)


Get future salts

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|num|[CLICK ME int](../types/int.md) | Yes|How many salts should be fetched|


### Return type: [FutureSalts](../types/FutureSalts.md)

### Can bots use this method: **YES**


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

$FutureSalts = $MadelineProto->get_future_salts(['num' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - get_future_salts
* params - `{"num": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/get_future_salts`

Parameters:

num - Json encoded int




Or, if you're into Lua:

```
FutureSalts = get_future_salts({num=int, })
```

