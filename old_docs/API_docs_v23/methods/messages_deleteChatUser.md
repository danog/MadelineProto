---
title: messages.deleteChatUser
description: messages.deleteChatUser parameters, return type and example
---
## Method: messages.deleteChatUser  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Optional|
|user\_id|[InputUser](../types/InputUser.md) | Optional|


### Return type: [messages\_StatedMessage](../types/messages_StatedMessage.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_ID_INVALID|The provided chat id is invalid|
|PEER_ID_INVALID|The provided peer id is invalid|
|USER_NOT_PARTICIPANT|You're not a member of this supergroup/channel|


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

$messages_StatedMessage = $MadelineProto->messages->deleteChatUser(['chat_id' => InputPeer, 'user_id' => InputUser, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.deleteChatUser
* params - `{"chat_id": InputPeer, "user_id": InputUser, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.deleteChatUser`

Parameters:

chat_id - Json encoded InputPeer

user_id - Json encoded InputUser




Or, if you're into Lua:

```
messages_StatedMessage = messages.deleteChatUser({chat_id=InputPeer, user_id=InputUser, })
```

