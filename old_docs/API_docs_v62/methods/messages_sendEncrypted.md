---
title: messages.sendEncrypted
description: messages.sendEncrypted parameters, return type and example
---
## Method: messages.sendEncrypted  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|peer|[InputEncryptedChat](../types/InputEncryptedChat.md) | Yes|
|message|[DecryptedMessage](../types/DecryptedMessage.md) | Yes|


### Return type: [messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$messages_SentEncryptedMessage = $MadelineProto->messages->sendEncrypted(['peer' => InputEncryptedChat, 'message' => DecryptedMessage, ]);
```

Or, if you're into Lua:

```
messages_SentEncryptedMessage = messages.sendEncrypted({peer=InputEncryptedChat, message=DecryptedMessage, })
```

