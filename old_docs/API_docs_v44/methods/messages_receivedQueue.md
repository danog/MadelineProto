---
title: messages.receivedQueue
description: messages.receivedQueue parameters, return type and example
---
## Method: messages.receivedQueue  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|max\_qts|[CLICK ME int](../types/int.md) | Yes|


### Return type: [Vector\_of\_long](../types/long.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MSG_WAIT_FAILED|A waiting call returned an error|


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

$Vector_of_long = $MadelineProto->messages->receivedQueue(['max_qts' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.receivedQueue
* params - `{"max_qts": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.receivedQueue`

Parameters:

max_qts - Json encoded int




Or, if you're into Lua:

```
Vector_of_long = messages.receivedQueue({max_qts=int, })
```

