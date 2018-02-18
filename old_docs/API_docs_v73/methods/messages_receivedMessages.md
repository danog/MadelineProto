---
title: messages.receivedMessages
description: messages.receivedMessages parameters, return type and example
---
## Method: messages.receivedMessages  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|max\_id|[int](../types/int.md) | Yes|


### Return type: [Vector\_of\_ReceivedNotifyMessage](../types/ReceivedNotifyMessage.md)

### Can bots use this method: **NO**


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$Vector_of_ReceivedNotifyMessage = $MadelineProto->messages->receivedMessages(['max_id' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.receivedMessages`

Parameters:

max_id - Json encoded int




Or, if you're into Lua:

```
Vector_of_ReceivedNotifyMessage = messages.receivedMessages({max_id=int, })
```

