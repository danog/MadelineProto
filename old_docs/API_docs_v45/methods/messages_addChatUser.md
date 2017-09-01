---
title: messages.addChatUser
description: messages.addChatUser parameters, return type and example
---
## Method: messages.addChatUser  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|
|user\_id|[InputUser](../types/InputUser.md) | Yes|
|fwd\_limit|[int](../types/int.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_ID_INVALID|The provided chat id is invalid|
|USER_ALREADY_PARTICIPANT|The user is already in the group|
|USER_ID_INVALID|The provided user ID is invalid|
|USER_NOT_MUTUAL_CONTACT|The provided user is not a mutual contact|
|USER_PRIVACY_RESTRICTED|The user's privacy settings do not allow you to do this|
|USERS_TOO_MUCH|The maximum number of users has been exceeded (to create a chat, for example)|


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

$Updates = $MadelineProto->messages->addChatUser(['chat_id' => InputPeer, 'user_id' => InputUser, 'fwd_limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.addChatUser`

Parameters:

chat_id - Json encoded InputPeer

user_id - Json encoded InputUser

fwd_limit - Json encoded int




Or, if you're into Lua:

```
Updates = messages.addChatUser({chat_id=InputPeer, user_id=InputUser, fwd_limit=int, })
```

