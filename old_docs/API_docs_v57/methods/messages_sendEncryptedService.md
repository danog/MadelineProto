---
title: messages.sendEncryptedService
description: messages.sendEncryptedService parameters, return type and example
---
## Method: messages.sendEncryptedService  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[Secret chat ID, Update, EncryptedMessage or InputEncryptedChat](../types/InputEncryptedChat.md) | Yes|
|message|[DecryptedMessage](../types/DecryptedMessage.md) | Yes|


### Return type: [messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|DATA_INVALID|Encrypted data invalid|
|ENCRYPTION_DECLINED|The secret chat was declined|
|MSG_WAIT_FAILED|A waiting call returned an error|
|USER_IS_BLOCKED|User is blocked|


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

$messages_SentEncryptedMessage = $MadelineProto->messages->sendEncryptedService(['peer' => InputEncryptedChat, 'message' => DecryptedMessage, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.sendEncryptedService
* params - `{"peer": InputEncryptedChat, "message": DecryptedMessage, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.sendEncryptedService`

Parameters:

peer - Json encoded InputEncryptedChat

message - Json encoded DecryptedMessage




Or, if you're into Lua:

```
messages_SentEncryptedMessage = messages.sendEncryptedService({peer=InputEncryptedChat, message=DecryptedMessage, })
```


## Return value 

If the length of the provided message is bigger than 4096, the message will be split in chunks and the method will be called multiple times, with the same parameters (except for the message), and an array of [messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md) will be returned instead.


