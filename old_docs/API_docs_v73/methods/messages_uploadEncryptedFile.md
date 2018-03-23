---
title: messages.uploadEncryptedFile
description: Upload a secret chat file without sending it to anyone
---
## Method: messages.uploadEncryptedFile  
[Back to methods index](index.md)


Upload a secret chat file without sending it to anyone

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Secret chat ID, Update, EncryptedMessage or InputEncryptedChat](../types/InputEncryptedChat.md) | Yes|The chat where to upload the media|
|file|[File path or InputEncryptedFile](../types/InputEncryptedFile.md) | Optional|The file|


### Return type: [EncryptedFile](../types/EncryptedFile.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$EncryptedFile = $MadelineProto->messages->uploadEncryptedFile(['peer' => InputEncryptedChat, 'file' => InputEncryptedFile, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.uploadEncryptedFile
* params - `{"peer": InputEncryptedChat, "file": InputEncryptedFile, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.uploadEncryptedFile`

Parameters:

peer - Json encoded InputEncryptedChat

file - Json encoded InputEncryptedFile




Or, if you're into Lua:

```
EncryptedFile = messages.uploadEncryptedFile({peer=InputEncryptedChat, file=InputEncryptedFile, })
```

