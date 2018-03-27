---
title: messages.reportEncryptedSpam
description: Report for spam a secret chat
---
## Method: messages.reportEncryptedSpam  
[Back to methods index](index.md)


Report for spam a secret chat

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Secret chat ID, Update, EncryptedMessage or InputEncryptedChat](../types/InputEncryptedChat.md) | Yes|The chat to report|


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

$Bool = $MadelineProto->messages->reportEncryptedSpam(['peer' => InputEncryptedChat, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.reportEncryptedSpam`

Parameters:

peer - Json encoded InputEncryptedChat




Or, if you're into Lua:

```
Bool = messages.reportEncryptedSpam({peer=InputEncryptedChat, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_ID_INVALID|The provided chat id is invalid|


