---
title: messages.setGameScore
description: messages.setGameScore parameters, return type and example
---
## Method: messages.setGameScore  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|edit\_message|[Bool](../types/Bool.md) | Optional|
|force|[Bool](../types/Bool.md) | Optional|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|
|id|[int](../types/int.md) | Yes|
|user\_id|[Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|
|score|[int](../types/int.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PEER_ID_INVALID|The provided peer id is invalid|
|USER_BOT_REQUIRED|This method can only be called by a bot|


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

$Updates = $MadelineProto->messages->setGameScore(['edit_message' => Bool, 'force' => Bool, 'peer' => InputPeer, 'id' => int, 'user_id' => InputUser, 'score' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.setGameScore
* params - `{"edit_message": Bool, "force": Bool, "peer": InputPeer, "id": int, "user_id": InputUser, "score": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.setGameScore`

Parameters:

edit_message - Json encoded Bool

force - Json encoded Bool

peer - Json encoded InputPeer

id - Json encoded int

user_id - Json encoded InputUser

score - Json encoded int




Or, if you're into Lua:

```
Updates = messages.setGameScore({edit_message=Bool, force=Bool, peer=InputPeer, id=int, user_id=InputUser, score=int, })
```

