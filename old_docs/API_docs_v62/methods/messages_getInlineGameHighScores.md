---
title: messages.getInlineGameHighScores
description: messages.getInlineGameHighScores parameters, return type and example
---
## Method: messages.getInlineGameHighScores  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[CLICK ME InputBotInlineMessageID](../types/InputBotInlineMessageID.md) | Yes|
|user\_id|[Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|


### Return type: [messages\_HighScores](../types/messages_HighScores.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MESSAGE_ID_INVALID|The provided message id is invalid|
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

