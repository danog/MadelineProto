---
title: messages.addChatUser
description: messages.addChatUser parameters, return type and example
---
## Method: messages.addChatUser  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|
|user\_id|[Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|
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

