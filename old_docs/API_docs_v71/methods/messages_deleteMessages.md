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
|revoke|[Bool](../types/Bool.md) | Optional|Delete the messages for everyone?|
|id|Array of [int](../types/int.md) | Yes|IDs of messages to delete, use channels->deleteMessages for supergroups|


### Return type: [messages\_AffectedMessages](../types/messages_AffectedMessages.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$messages_AffectedMessages = $MadelineProto->messages->deleteMessages(['revoke' => Bool, 'id' => [int, int], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.deleteMessages
* params - `{"revoke": Bool, "id": [int], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.deleteMessages`

Parameters:

revoke - Json encoded Bool

id - Json encoded  array of int




Or, if you're into Lua:

```
messages_AffectedMessages = messages.deleteMessages({revoke=Bool, id={int}, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MESSAGE_DELETE_FORBIDDEN|You can't delete one of the messages you tried to delete, most likely because it is a service message.|


