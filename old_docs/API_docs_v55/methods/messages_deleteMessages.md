---
title: messages.deleteMessages
description: messages.deleteMessages parameters, return type and example
---
## Method: messages.deleteMessages  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|Array of [int](../types/int.md) | Yes|


### Return type: [messages\_AffectedMessages](../types/messages_AffectedMessages.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MESSAGE_DELETE_FORBIDDEN|You can't delete one of the messages you tried to delete, most likely because it is a service message.|


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

$messages_AffectedMessages = $MadelineProto->messages->deleteMessages(['id' => [int], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

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
messages_AffectedMessages = messages.deleteMessages({id={int}, })
```

