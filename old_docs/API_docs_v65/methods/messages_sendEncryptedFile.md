---
title: messages.sendEncryptedFile
description: messages.sendEncryptedFile parameters, return type and example
---
## Method: messages.sendEncryptedFile  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputEncryptedChat](../types/InputEncryptedChat.md) | Yes|
|message|[DecryptedMessage](../types/DecryptedMessage.md) | Yes|
|file|[InputEncryptedFile](../types/InputEncryptedFile.md) | Yes|


### Return type: [messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MSG_WAIT_FAILED|A waiting call returned an error|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$messages_SentEncryptedMessage = $MadelineProto->messages->sendEncryptedFile(['peer' => InputEncryptedChat, 'message' => DecryptedMessage, 'file' => InputEncryptedFile, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

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


