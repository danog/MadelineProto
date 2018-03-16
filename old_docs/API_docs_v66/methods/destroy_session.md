---
title: destroy_session
description: destroy_session parameters, return type and example
---
## Method: destroy\_session  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|session\_id|[long](../types/long.md) | Yes|


### Return type: [DestroySessionRes](../types/DestroySessionRes.md)

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

$DestroySessionRes = $MadelineProto->destroy_session(['session_id' => long, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

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

