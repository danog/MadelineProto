---
title: destroy_session
description: Destroy the current MTProto session
---
## Method: destroy\_session  
[Back to methods index](index.md)


Destroy the current MTProto session

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|session\_id|[CLICK ME long](../types/long.md) | Yes|The session to destroy|


### Return type: [DestroySessionRes](../types/DestroySessionRes.md)

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

$DestroySessionRes = $MadelineProto->destroy_session(['session_id' => long, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - destroy_session
* params - `{"session_id": long, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/destroy_session`

Parameters:

session_id - Json encoded long




Or, if you're into Lua:

```
DestroySessionRes = destroy_session({session_id=long, })
```

