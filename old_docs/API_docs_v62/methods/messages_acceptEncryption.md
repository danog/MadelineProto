---
title: messages.acceptEncryption
description: messages.acceptEncryption parameters, return type and example
---
## Method: messages.acceptEncryption  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|peer|[InputEncryptedChat](../types/InputEncryptedChat.md) | Yes|
|g\_b|[bytes](../types/bytes.md) | Yes|
|key\_fingerprint|[long](../types/long.md) | Yes|


### Return type: [EncryptedChat](../types/EncryptedChat.md)

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

$EncryptedChat = $MadelineProto->messages->acceptEncryption(['peer' => InputEncryptedChat, 'g_b' => bytes, 'key_fingerprint' => long, ]);
```

Or, if you're into Lua:

```
EncryptedChat = messages.acceptEncryption({peer=InputEncryptedChat, g_b=bytes, key_fingerprint=long, })
```

