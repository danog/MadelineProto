---
title: auth.requestPasswordRecovery
description: auth.requestPasswordRecovery parameters, return type and example
---
## Method: auth.requestPasswordRecovery  
[Back to methods index](index.md)




### Return type: [auth\_PasswordRecovery](../types/auth_PasswordRecovery.md)

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

$auth_PasswordRecovery = $MadelineProto->auth->requestPasswordRecovery();
```