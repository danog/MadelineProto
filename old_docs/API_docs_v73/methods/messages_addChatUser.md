---
title: messages.addChatUser
description: messages.addChatUser parameters, return type and example
---
## Method: messages.addChatUser  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Optional|
|user\_id|[InputUser](../types/InputUser.md) | Optional|
|fwd\_limit|[int](../types/int.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|CHAT_ID_INVALID|The provided chat id is invalid|
|INPUT_USER_DEACTIVATED|The specified user was deleted|
|PEER_ID_INVALID|The provided peer id is invalid|
|USER_ALREADY_PARTICIPANT|The user is already in the group|
|USER_ID_INVALID|The provided user ID is invalid|
|USERS_TOO_MUCH|The maximum number of users has been exceeded (to create a chat, for example)|
|USER_NOT_MUTUAL_CONTACT|The provided user is not a mutual contact|
|USER_PRIVACY_RESTRICTED|The user's privacy settings do not allow you to do this|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
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

