---
title: messages.receivedQueue
description: messages.receivedQueue parameters, return type and example
---
## Method: messages.receivedQueue  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|max\_qts|[int](../types/int.md) | Yes|


### Return type: [Vector\_of\_long](../types/long.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MSG_WAIT_FAILED|A waiting call returned an error|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$Vector_of_long = $MadelineProto->messages->receivedQueue(['max_qts' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

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

