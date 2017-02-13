---
title: auth.importBotAuthorization
description: auth.importBotAuthorization parameters, return type and example
---
## Method: auth.importBotAuthorization  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|api\_id|[int](../types/int.md) | Required|
|api\_hash|[string](../types/string.md) | Required|
|bot\_auth\_token|[string](../types/string.md) | Required|


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

$auth_Authorization = $MadelineProto->auth->importBotAuthorization(['api_id' => int, 'api_hash' => string, 'bot_auth_token' => string, ]);
```
