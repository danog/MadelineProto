---
title: auth.resendCode
description: auth.resendCode parameters, return type and example
---
## Method: auth.resendCode  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|phone\_number|[string](../types/string.md) | Yes|
|phone\_code\_hash|[string](../types/string.md) | Yes|


### Return type: [auth\_SentCode](../types/auth_SentCode.md)

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

$auth_SentCode = $MadelineProto->auth->resendCode(['phone_number' => string, 'phone_code_hash' => string, ]);
```

Or, if you're into Lua:

```
auth_SentCode = auth.resendCode({phone_number=string, phone_code_hash=string, })
```

