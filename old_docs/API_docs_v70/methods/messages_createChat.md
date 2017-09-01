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


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|USER_RESTRICTED|You're spamreported, you can't create channels or chats.|
|USERS_TOO_FEW|Not enough users (to create a chat, for example)|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Updates = $MadelineProto->messages->createChat(['users' => [InputUser], 'title' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.createChat`

Parameters:

users - Json encoded  array of InputUser

title - Json encoded string




Or, if you're into Lua:

```
Updates = messages.createChat({users={InputUser}, title='string', })
```

