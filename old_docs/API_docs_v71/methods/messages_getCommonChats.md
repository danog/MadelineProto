---
title: messages.getCommonChats
description: messages.getCommonChats parameters, return type and example
---
## Method: messages.getCommonChats  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|user\_id|[InputUser](../types/InputUser.md) | Yes|
|max\_id|[int](../types/int.md) | Yes|
|limit|[int](../types/int.md) | Yes|


### Return type: [messages\_Chats](../types/messages_Chats.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|USER_ID_INVALID|The provided user ID is invalid|


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

$messages_Chats = $MadelineProto->messages->getCommonChats(['user_id' => InputUser, 'max_id' => int, 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



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

