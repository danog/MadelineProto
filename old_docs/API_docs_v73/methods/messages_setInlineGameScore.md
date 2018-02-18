---
title: messages.setInlineGameScore
description: messages.setInlineGameScore parameters, return type and example
---
## Method: messages.setInlineGameScore  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|edit\_message|[Bool](../types/Bool.md) | Optional|
|force|[Bool](../types/Bool.md) | Optional|
|id|[InputBotInlineMessageID](../types/InputBotInlineMessageID.md) | Yes|
|user\_id|[InputUser](../types/InputUser.md) | Optional|
|score|[int](../types/int.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MESSAGE_ID_INVALID|The provided message id is invalid|
|USER_BOT_REQUIRED|This method can only be called by a bot|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$Bool = $MadelineProto->messages->setInlineGameScore(['edit_message' => Bool, 'force' => Bool, 'id' => InputBotInlineMessageID, 'user_id' => InputUser, 'score' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

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

