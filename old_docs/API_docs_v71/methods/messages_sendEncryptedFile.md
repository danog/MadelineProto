---
title: messages.sendEncryptedFile
description: Send a file to a secret chat
---
## Method: messages.sendEncryptedFile  
[Back to methods index](index.md)


Send a file to a secret chat

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Secret chat ID, Update, EncryptedMessage or InputEncryptedChat](../types/InputEncryptedChat.md) | Yes|The chat where to send the file|
|message|[DecryptedMessage](../types/DecryptedMessage.md) | Yes|The message with the file|
|file|[File path or InputEncryptedFile](../types/InputEncryptedFile.md) | Optional|The file (optional)|


### Return type: [messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$messages_SentEncryptedMessage = $MadelineProto->messages->sendEncryptedFile(['peer' => InputEncryptedChat, 'message' => DecryptedMessage, 'file' => InputEncryptedFile, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.sendEncryptedFile
* params - `{"peer": InputEncryptedChat, "message": DecryptedMessage, "file": InputEncryptedFile, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.sendEncryptedFile`

Parameters:

peer - Json encoded InputEncryptedChat

message - Json encoded DecryptedMessage

file - Json encoded InputEncryptedFile




Or, if you're into Lua:

```
messages_SentEncryptedMessage = messages.sendEncryptedFile({peer=InputEncryptedChat, message=DecryptedMessage, file=InputEncryptedFile, })
```


## Return value 

If the length of the provided message is bigger than 4096, the message will be split in chunks and the method will be called multiple times, with the same parameters (except for the message), and an array of [messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md) will be returned instead.


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MSG_WAIT_FAILED|A waiting call returned an error|


