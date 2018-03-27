---
title: messages.readEncryptedHistory
description: Mark messages as read in secret chats
---
## Method: messages.readEncryptedHistory  
[Back to methods index](index.md)


Mark messages as read in secret chats

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Secret chat ID, Update, EncryptedMessage or InputEncryptedChat](../types/InputEncryptedChat.md) | Yes|The secret chat where to mark messages as read|
|max\_date|[int](../types/int.md) | Yes|Maximum date of messages to mark|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Bool = $MadelineProto->messages->readEncryptedHistory(['peer' => InputEncryptedChat, 'max_date' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.readEncryptedHistory
* params - `{"peer": InputEncryptedChat, "max_date": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.readEncryptedHistory`

Parameters:

peer - Json encoded InputEncryptedChat

max_date - Json encoded int




Or, if you're into Lua:

```
Bool = messages.readEncryptedHistory({peer=InputEncryptedChat, max_date=int, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MSG_WAIT_FAILED|A waiting call returned an error|


