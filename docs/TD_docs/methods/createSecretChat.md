---
title: createSecretChat
description: Returns existing chat corresponding to the known secret chat
---
## Method: createSecretChat  
[Back to methods index](index.md)


Returns existing chat corresponding to the known secret chat

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|secret\_chat\_id|[int](../types/int.md) | Yes|SecretChat identifier|


### Return type: [Chat](../types/Chat.md)

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

$Chat = $MadelineProto->createSecretChat(['secret_chat_id' => int, ]);
```

Or, if you're into Lua:

```
Chat = createSecretChat({secret_chat_id=int, })
```

