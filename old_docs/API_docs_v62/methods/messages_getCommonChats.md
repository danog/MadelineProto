---
title: messages.getCommonChats
description: Get chats in common with a user
---
## Method: messages.getCommonChats  
[Back to methods index](index.md)


Get chats in common with a user

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|user\_id|[Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|The user|
|max\_id|[int](../types/int.md) | Yes|The maximum chat ID to fetch|
|limit|[int](../types/int.md) | Yes|Number of results to fetch|


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

$messages_Chats = $MadelineProto->messages->getCommonChats(['user_id' => InputUser, 'max_id' => int, 'limit' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getCommonChats`

Parameters:

user_id - Json encoded InputUser

max_id - Json encoded int

limit - Json encoded int




Or, if you're into Lua:

```
messages_Chats = messages.getCommonChats({user_id=InputUser, max_id=int, limit=int, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|USER_ID_INVALID|The provided user ID is invalid|


