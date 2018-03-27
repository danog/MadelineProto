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
|peer|[Secret chat ID, Update, EncryptedMessage or InputEncryptedChat](../types/InputEncryptedChat.md) | Yes|The secret chat where to send the notification|
|typing|[Bool](../types/Bool.md) | Yes|Set to true to enable the notification, false to disable it|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
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

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_ID_INVALID|The provided chat id is invalid|


