---
title: auth_requestPasswordRecovery
description: auth_requestPasswordRecovery parameters, return type and example
---
## Method: auth\_requestPasswordRecovery  
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

$auth_PasswordRecovery = $MadelineProto->auth_requestPasswordRecovery();
```