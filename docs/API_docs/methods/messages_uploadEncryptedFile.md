---
title: messages.uploadEncryptedFile
description: messages.uploadEncryptedFile parameters, return type and example
---
## Method: messages.uploadEncryptedFile  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputEncryptedChat](../types/InputEncryptedChat.md) | Yes|
|file|[InputEncryptedFile](../types/InputEncryptedFile.md) | Yes|


### Return type: [EncryptedFile](../types/EncryptedFile.md)

### Can bots use this method: **YES**


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

$EncryptedFile = $MadelineProto->messages->uploadEncryptedFile(['peer' => InputEncryptedChat, 'file' => InputEncryptedFile, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

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

