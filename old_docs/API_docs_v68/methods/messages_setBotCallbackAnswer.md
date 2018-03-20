---
title: messages.setBotCallbackAnswer
description: messages.setBotCallbackAnswer parameters, return type and example
---
## Method: messages.setBotCallbackAnswer  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|alert|[CLICK ME Bool](../types/Bool.md) | Optional|
|query\_id|[CLICK ME long](../types/long.md) | Yes|
|message|[CLICK ME string](../types/string.md) | Optional|
|url|[CLICK ME string](../types/string.md) | Optional|
|cache\_time|[CLICK ME int](../types/int.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|QUERY_ID_INVALID|The query ID is invalid|


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

$Bool = $MadelineProto->messages->setBotCallbackAnswer(['alert' => Bool, 'query_id' => long, 'message' => 'string', 'url' => 'string', 'cache_time' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.setBotCallbackAnswer
* params - `{"alert": Bool, "query_id": long, "message": "string", "url": "string", "cache_time": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.setBotCallbackAnswer`

Parameters:

alert - Json encoded Bool

query_id - Json encoded long

message - Json encoded string

url - Json encoded string

cache_time - Json encoded int




Or, if you're into Lua:

```
Bool = messages.setBotCallbackAnswer({alert=Bool, query_id=long, message='string', url='string', cache_time=int, })
```


## Return value 

If the length of the provided message is bigger than 4096, the message will be split in chunks and the method will be called multiple times, with the same parameters (except for the message), and an array of [Bool](../types/Bool.md) will be returned instead.


