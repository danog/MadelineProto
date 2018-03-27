---
title: messages.getAllChats
description: Get all chats (not supergroups or channels)
---
## Method: messages.getAllChats  
[Back to methods index](index.md)


Get all chats (not supergroups or channels)

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|except\_ids|Array of [int](../types/int.md) | Yes|Do not fetch these chats (MTProto id)|


### Return type: [messages\_Chats](../types/messages_Chats.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$messages_Chats = $MadelineProto->messages->getAllChats(['except_ids' => [int, int], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getAllChats`

Parameters:

except_ids - Json encoded  array of int




Or, if you're into Lua:

```
messages_Chats = messages.getAllChats({except_ids={int}, })
```

