---
title: getSecretChat
description: Returns information about a secret chat by its identifier, offline request
---
## Method: getSecretChat  
[Back to methods index](index.md)


Returns information about a secret chat by its identifier, offline request

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|secret\_chat\_id|[int](../types/int.md) | Yes|Secret chat identifier|


### Return type: [SecretChat](../types/SecretChat.md)

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

$SecretChat = $MadelineProto->getSecretChat(['secret_chat_id' => int, ]);
```

Or, if you're into Lua:

```
SecretChat = getSecretChat({secret_chat_id=int, })
```

