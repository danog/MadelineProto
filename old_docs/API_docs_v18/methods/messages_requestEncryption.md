---
title: messages.requestEncryption
description: messages.requestEncryption parameters, return type and example
---
## Method: messages.requestEncryption  
[Back to methods index](index.md)


*You cannot use this method directly, see https://daniil.it/MadelineProto for more info on handling secret chats*




### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|user\_id|[InputUser](../types/InputUser.md) | Yes|
|g\_a|[bytes](../types/bytes.md) | Yes|


### Return type: [EncryptedChat](../types/EncryptedChat.md)

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

$EncryptedChat = $MadelineProto->messages->requestEncryption(['user_id' => InputUser, 'g_a' => 'bytes', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.requestEncryption
* params - `{"user_id": InputUser, "g_a": "bytes", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.requestEncryption`

Parameters:

user_id - Json encoded InputUser
g_a - Json encoded bytes



Or, if you're into Lua:

```
EncryptedChat = messages.requestEncryption({user_id=InputUser, g_a='bytes', })
```

