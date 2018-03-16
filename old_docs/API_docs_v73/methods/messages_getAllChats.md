---
title: messages.getAllChats
description: messages.getAllChats parameters, return type and example
---
## Method: messages.getAllChats  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|except\_ids|Array of [int](../types/int.md) | Yes|


### Return type: [messages\_Chats](../types/messages_Chats.md)

### Can bots use this method: **NO**


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

$messages_Chats = $MadelineProto->messages->getAllChats(['except_ids' => [int], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getAllChats`

Parameters:

except_ids - Json encoded  array of int




Or, if you're into Lua:

```
messages_Chats = messages.getAllChats({except_ids={int}, })
```

