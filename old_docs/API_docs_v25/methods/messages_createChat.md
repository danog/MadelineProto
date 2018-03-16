---
title: messages.createChat
description: messages.createChat parameters, return type and example
---
## Method: messages.createChat  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|users|Array of [InputUser](../types/InputUser.md) | Yes|
|title|[string](../types/string.md) | Yes|


### Return type: [messages\_StatedMessage](../types/messages_StatedMessage.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|USERS_TOO_FEW|Not enough users (to create a chat, for example)|
|USER_RESTRICTED|You're spamreported, you can't create channels or chats.|


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

$messages_StatedMessage = $MadelineProto->messages->createChat(['users' => [InputUser], 'title' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.createChat`

Parameters:

users - Json encoded  array of InputUser

title - Json encoded string




Or, if you're into Lua:

```
messages_StatedMessage = messages.createChat({users={InputUser}, title='string', })
```

