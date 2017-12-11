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
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

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

