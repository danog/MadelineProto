---
title: messages.deleteMessages
description: Delete messages
---
## Method: messages.deleteMessages  
[Back to methods index](index.md)


Delete messages

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|Array of [int](../types/int.md) | Yes|IDs of messages to delete, use channels->deleteMessages for supergroups|


### Return type: [Vector\_of\_int](../types/int.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Vector_of_int = $MadelineProto->messages->deleteMessages(['id' => [int, int], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.deleteMessages
* params - `{"id": [int], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.deleteMessages`

Parameters:

id - Json encoded  array of int




Or, if you're into Lua:

```
Vector_of_int = messages.deleteMessages({id={int}, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MESSAGE_DELETE_FORBIDDEN|You can't delete one of the messages you tried to delete, most likely because it is a service message.|


