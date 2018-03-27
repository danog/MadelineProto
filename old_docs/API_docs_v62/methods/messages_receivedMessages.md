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
|max\_id|[int](../types/int.md) | Yes|Maximum message id of messages to mark as read|


### Return type: [Vector\_of\_ReceivedNotifyMessage](../types/ReceivedNotifyMessage.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Vector_of_ReceivedNotifyMessage = $MadelineProto->messages->receivedMessages(['max_id' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.receivedMessages`

Parameters:

max_id - Json encoded int




Or, if you're into Lua:

```
Vector_of_ReceivedNotifyMessage = messages.receivedMessages({max_id=int, })
```

