---
title: messages.sendEncrypted
description: Send message to secret chat
---
## Method: messages.sendEncrypted  
[Back to methods index](index.md)


Send message to secret chat

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Secret chat ID, Update, EncryptedMessage or InputEncryptedChat](../types/InputEncryptedChat.md) | Yes|The chat where to send the message|
|message|[DecryptedMessage](../types/DecryptedMessage.md) | Yes|The message to send|


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

$messages_SentEncryptedMessage = $MadelineProto->messages->sendEncrypted(['peer' => InputEncryptedChat, 'message' => DecryptedMessage, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.sendEncrypted
* params - `{"peer": InputEncryptedChat, "message": DecryptedMessage, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.sendEncrypted`

Parameters:

peer - Json encoded InputEncryptedChat

message - Json encoded DecryptedMessage




Or, if you're into Lua:

```
messages_SentEncryptedMessage = messages.sendEncrypted({peer=InputEncryptedChat, message=DecryptedMessage, })
```


## Return value 

If the length of the provided message is bigger than 4096, the message will be split in chunks and the method will be called multiple times, with the same parameters (except for the message), and an array of [messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md) will be returned instead.


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_ID_INVALID|The provided chat id is invalid|
|DATA_INVALID|Encrypted data invalid|
|ENCRYPTION_DECLINED|The secret chat was declined|
|MSG_WAIT_FAILED|A waiting call returned an error|


