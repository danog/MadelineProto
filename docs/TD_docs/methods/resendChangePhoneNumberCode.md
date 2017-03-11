---
title: resendChangePhoneNumberCode
description: Resends authentication code sent to change user's phone number. Wotks only if in previously received authStateWaitCode next_code_type was not null. Returns authStateWaitCode on success
---
## Method: resendChangePhoneNumberCode  
[Back to methods index](index.md)


Resends authentication code sent to change user's phone number. Wotks only if in previously received authStateWaitCode next_code_type was not null. Returns authStateWaitCode on success

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|


### Return type: [AuthState](../types/AuthState.md)

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

$AuthState = $MadelineProto->resendChangePhoneNumberCode();
```

Or, if you're into Lua:

```
AuthState = resendChangePhoneNumberCode({})
```

