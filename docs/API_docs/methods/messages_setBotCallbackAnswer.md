---
title: messages.setBotCallbackAnswer
description: Bots only: set the callback answer (after a button was clicked)
---
## Method: messages.setBotCallbackAnswer  
[Back to methods index](index.md)


Bots only: set the callback answer (after a button was clicked)

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|alert|[CLICK ME Bool](../types/Bool.md) | Optional|Is this an alert?|
|query\_id|[CLICK ME long](../types/long.md) | Yes|The query ID|
|message|[CLICK ME string](../types/string.md) | Optional|The message|
|url|[CLICK ME string](../types/string.md) | Optional|The URL|
|cache\_time|[CLICK ME int](../types/int.md) | Yes|Cache time|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|QUERY_ID_INVALID|The query ID is invalid|


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Bool = $MadelineProto->messages->setBotCallbackAnswer(['alert' => Bool, 'query_id' => long, 'message' => 'string', 'url' => 'string', 'cache_time' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

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


