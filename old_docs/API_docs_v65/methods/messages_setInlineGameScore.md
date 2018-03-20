---
title: messages.setInlineGameScore
description: messages.setInlineGameScore parameters, return type and example
---
## Method: messages.setInlineGameScore  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|edit\_message|[CLICK ME Bool](../types/Bool.md) | Optional|
|force|[CLICK ME Bool](../types/Bool.md) | Optional|
|id|[CLICK ME InputBotInlineMessageID](../types/InputBotInlineMessageID.md) | Yes|
|user\_id|[Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|
|score|[CLICK ME int](../types/int.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MESSAGE_ID_INVALID|The provided message id is invalid|
|USER_BOT_REQUIRED|This method can only be called by a bot|


### MadelineProto Example:


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

$Bool = $MadelineProto->messages->setInlineGameScore(['edit_message' => Bool, 'force' => Bool, 'id' => InputBotInlineMessageID, 'user_id' => InputUser, 'score' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.setInlineGameScore
* params - `{"edit_message": Bool, "force": Bool, "id": InputBotInlineMessageID, "user_id": InputUser, "score": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.setInlineGameScore`

Parameters:

edit_message - Json encoded Bool

force - Json encoded Bool

id - Json encoded InputBotInlineMessageID

user_id - Json encoded InputUser

score - Json encoded int




Or, if you're into Lua:

```
Bool = messages.setInlineGameScore({edit_message=Bool, force=Bool, id=InputBotInlineMessageID, user_id=InputUser, score=int, })
```

