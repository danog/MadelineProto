---
title: messages.sendEncryptedFile
description: messages.sendEncryptedFile parameters, return type and example
---
## Method: messages.sendEncryptedFile  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|peer|[InputEncryptedChat](../types/InputEncryptedChat.md) | Yes|
|message|[DecryptedMessage](../types/DecryptedMessage.md) | Yes|
|file|[InputEncryptedFile](../types/InputEncryptedFile.md) | Yes|


### Return type: [messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $this->bot_login($token);
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

Or, if you're into Lua:

```
messages_SentEncryptedMessage = messages.sendEncryptedFile({peer=InputEncryptedChat, message=DecryptedMessage, file=InputEncryptedFile, })
```

