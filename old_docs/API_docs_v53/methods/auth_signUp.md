---
title: auth.signUp
description: auth.signUp parameters, return type and example
---
## Method: auth.signUp  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|phone\_number|[string](../types/string.md) | Required|
|phone\_code\_hash|[string](../types/string.md) | Required|
|phone\_code|[string](../types/string.md) | Required|
|first\_name|[string](../types/string.md) | Required|
|last\_name|[string](../types/string.md) | Required|


### Return type: [auth\_Authorization](../types/auth_Authorization.md)

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

$auth_Authorization = $MadelineProto->auth->signUp(['phone_number' => string, 'phone_code_hash' => string, 'phone_code' => string, 'first_name' => string, 'last_name' => string, ]);
```