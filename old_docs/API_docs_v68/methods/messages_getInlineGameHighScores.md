---
title: messages.getInlineGameHighScores
description: messages.getInlineGameHighScores parameters, return type and example
---
## Method: messages.getInlineGameHighScores  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[InputBotInlineMessageID](../types/InputBotInlineMessageID.md) | Yes|
|user\_id|[InputUser](../types/InputUser.md) | Optional|


### Return type: [messages\_HighScores](../types/messages_HighScores.md)

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

$messages_HighScores = $MadelineProto->messages->getInlineGameHighScores(['id' => InputBotInlineMessageID, 'user_id' => InputUser, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.getInlineGameHighScores
* params - `{"id": InputBotInlineMessageID, "user_id": InputUser, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getInlineGameHighScores`

Parameters:

id - Json encoded InputBotInlineMessageID

user_id - Json encoded InputUser




Or, if you're into Lua:

```
messages_HighScores = messages.getInlineGameHighScores({id=InputBotInlineMessageID, user_id=InputUser, })
```

