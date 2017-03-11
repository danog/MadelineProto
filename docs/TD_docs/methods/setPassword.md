---
title: setPassword
description: Changes user password. If new recovery email is specified, then error EMAIL_UNCONFIRMED is returned and password change will not be applied until email confirmation. Application should call getPasswordState from time to time to check if email is already confirmed
---
## Method: setPassword  
[Back to methods index](index.md)


Changes user password. If new recovery email is specified, then error EMAIL_UNCONFIRMED is returned and password change will not be applied until email confirmation. Application should call getPasswordState from time to time to check if email is already confirmed

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|old\_password|[string](../types/string.md) | Yes|Old user password|
|new\_password|[string](../types/string.md) | Yes|New user password, may be empty to remove the password|
|new\_hint|[string](../types/string.md) | Yes|New password hint, can be empty|
|set\_recovery\_email|[Bool](../types/Bool.md) | Yes|Pass True, if recovery email should be changed|
|new\_recovery\_email|[string](../types/string.md) | Yes|New recovery email, may be empty|


### Return type: [PasswordState](../types/PasswordState.md)

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

$PasswordState = $MadelineProto->setPassword(['old_password' => string, 'new_password' => string, 'new_hint' => string, 'set_recovery_email' => Bool, 'new_recovery_email' => string, ]);
```

Or, if you're into Lua:

```
PasswordState = setPassword({old_password=string, new_password=string, new_hint=string, set_recovery_email=Bool, new_recovery_email=string, })
```

