---
title: http_wait
description: http_wait parameters, return type and example
---
## Method: http\_wait  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|max\_delay|[int](../types/int.md) | Yes|
|wait\_after|[int](../types/int.md) | Yes|
|max\_wait|[int](../types/int.md) | Yes|


### Return type: [HttpWait](../types/HttpWait.md)

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

$HttpWait = $MadelineProto->http_wait(['max_delay' => int, 'wait_after' => int, 'max_wait' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - http_wait
* params - `{"max_delay": int, "wait_after": int, "max_wait": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/http_wait`

Parameters:

max_delay - Json encoded int

wait_after - Json encoded int

max_wait - Json encoded int




Or, if you're into Lua:

```
HttpWait = http_wait({max_delay=int, wait_after=int, max_wait=int, })
```

