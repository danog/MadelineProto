---
title: messages.setEncryptedTyping
description: Send typing notification to secret chat
---
## Method: messages.setEncryptedTyping  
[Back to methods index](index.md)


Send typing notification to secret chat

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Secret chat ID, Update, EncryptedMessage or InputEncryptedChat](../types/InputEncryptedChat.md) | Yes||
|typing|[CLICK ME Bool](../types/Bool.md) | Yes||


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_ID_INVALID|The provided chat id is invalid|


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

$Bool = $MadelineProto->messages->setEncryptedTyping(['peer' => InputEncryptedChat, 'typing' => Bool, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.setEncryptedTyping`

Parameters:

peer - Json encoded InputEncryptedChat

typing - Json encoded Bool




Or, if you're into Lua:

```
Bool = messages.setEncryptedTyping({peer=InputEncryptedChat, typing=Bool, })
```

