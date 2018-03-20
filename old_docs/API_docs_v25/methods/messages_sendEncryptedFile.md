---
title: messages.sendEncryptedFile
description: messages.sendEncryptedFile parameters, return type and example
---
## Method: messages.sendEncryptedFile  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[Secret chat ID, Update, EncryptedMessage or InputEncryptedChat](../types/InputEncryptedChat.md) | Yes|
|message|[CLICK ME DecryptedMessage](../types/DecryptedMessage.md) | Yes|
|file|[File path or InputEncryptedFile](../types/InputEncryptedFile.md) | Optional|


### Return type: [messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MSG_WAIT_FAILED|A waiting call returned an error|


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


