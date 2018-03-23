---
title: messages.receivedMessages
description: Mark messages as read
---
## Method: messages.receivedMessages  
[Back to methods index](index.md)


Mark messages as read

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|max\_id|[CLICK ME int](../types/int.md) | Yes|Maximum message id of messages to mark as read|


### Return type: [Vector\_of\_int](../types/int.md)

### Can bots use this method: **NO**


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

$Vector_of_int = $MadelineProto->messages->receivedMessages(['max_id' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.receivedMessages`

Parameters:

max_id - Json encoded int




Or, if you're into Lua:

```
Vector_of_int = messages.receivedMessages({max_id=int, })
```

