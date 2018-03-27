---
title: messages.createChat
description: Create a chat (not supergroup)
---
## Method: messages.createChat  
[Back to methods index](index.md)


Create a chat (not supergroup)

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|users|Array of [Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Yes|The users to add to the chat|
|title|[string](../types/string.md) | Yes|The new chat's title|


### Return type: [messages\_StatedMessage](../types/messages_StatedMessage.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$messages_StatedMessage = $MadelineProto->messages->createChat(['users' => [InputUser, InputUser], 'title' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.createChat`

Parameters:

users - Json encoded  array of InputUser

title - Json encoded string




Or, if you're into Lua:

```
messages_StatedMessage = messages.createChat({users={InputUser}, title='string', })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|USERS_TOO_FEW|Not enough users (to create a chat, for example)|
|USER_RESTRICTED|You're spamreported, you can't create channels or chats.|


