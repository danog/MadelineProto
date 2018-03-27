---
title: messages.addChatUser
description: Add a user to a normal chat (use channels->inviteToChannel for supergroups)
---
## Method: messages.addChatUser  
[Back to methods index](index.md)


Add a user to a normal chat (use channels->inviteToChannel for supergroups)

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|The chat where to invite users|
|user\_id|[Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|The user to invite|
|fwd\_limit|[int](../types/int.md) | Yes|Number of old messages the user will see|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Updates = $MadelineProto->messages->addChatUser(['chat_id' => InputPeer, 'user_id' => InputUser, 'fwd_limit' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



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


