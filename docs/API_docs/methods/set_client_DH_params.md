---
title: set_client_DH_params
description: set_client_DH_params parameters, return type and example
---
## Method: set\_client\_DH\_params  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|nonce|[int128](../types/int128.md) | Yes|
|server\_nonce|[int128](../types/int128.md) | Yes|
|encrypted\_data|[string](../types/string.md) | Yes|


### Return type: [Set\_client\_DH\_params\_answer](../types/Set_client_DH_params_answer.md)

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

$Set_client_DH_params_answer = $MadelineProto->set_client_DH_params(['nonce' => int128, 'server_nonce' => int128, 'encrypted_data' => string, ]);
```

Or, if you're into Lua:

```
Set_client_DH_params_answer = set_client_DH_params({nonce=int128, server_nonce=int128, encrypted_data=string, })
```

