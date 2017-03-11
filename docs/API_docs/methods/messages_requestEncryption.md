---
title: messages.requestEncryption
description: messages.requestEncryption parameters, return type and example
---
## Method: messages.requestEncryption  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|user\_id|[InputUser](../types/InputUser.md) | Yes|
|g\_a|[bytes](../types/bytes.md) | Yes|


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

$EncryptedChat = $MadelineProto->messages->requestEncryption(['user_id' => InputUser, 'g_a' => bytes, ]);
```

Or, if you're into Lua:

```
EncryptedChat = messages.requestEncryption({user_id=InputUser, g_a=bytes, })
```

