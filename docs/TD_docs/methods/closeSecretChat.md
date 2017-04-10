---
title: closeSecretChat
description: Closes secret chat
---
## Method: closeSecretChat  
[Back to methods index](index.md)


Closes secret chat

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|secret\_chat\_id|[int](../types/int.md) | Yes|Secret chat identifier|


### Return type: [Ok](../types/Ok.md)

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

$Ok = $MadelineProto->closeSecretChat(['secret_chat_id' => int, ]);
```

Or, if you're into Lua:

```
Ok = closeSecretChat({secret_chat_id=int, })
```

