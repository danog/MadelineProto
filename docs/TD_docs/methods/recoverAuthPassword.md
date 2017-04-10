---
title: recoverAuthPassword
description: Recovers password with recovery code sent to email. Works only when authGetState returns authStateWaitPassword. Returns authStateOk on success
---
## Method: recoverAuthPassword  
[Back to methods index](index.md)


Recovers password with recovery code sent to email. Works only when authGetState returns authStateWaitPassword. Returns authStateOk on success

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|recovery\_code|[string](../types/string.md) | Yes|Recovery code to check|


### Return type: [AuthState](../types/AuthState.md)

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

$AuthState = $MadelineProto->recoverAuthPassword(['recovery_code' => string, ]);
```

Or, if you're into Lua:

```
AuthState = recoverAuthPassword({recovery_code=string, })
```

