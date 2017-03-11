---
title: req_DH_params
description: req_DH_params parameters, return type and example
---
## Method: req\_DH\_params  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|nonce|[int128](../types/int128.md) | Yes|
|server\_nonce|[int128](../types/int128.md) | Yes|
|p|[bytes](../types/bytes.md) | Yes|
|q|[bytes](../types/bytes.md) | Yes|
|public\_key\_fingerprint|[long](../types/long.md) | Yes|
|encrypted\_data|[bytes](../types/bytes.md) | Yes|


### Return type: [Server\_DH\_Params](../types/Server_DH_Params.md)

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

$Server_DH_Params = $MadelineProto->req_DH_params(['nonce' => int128, 'server_nonce' => int128, 'p' => bytes, 'q' => bytes, 'public_key_fingerprint' => long, 'encrypted_data' => bytes, ]);
```

Or, if you're into Lua:

```
Server_DH_Params = req_DH_params({nonce=int128, server_nonce=int128, p=bytes, q=bytes, public_key_fingerprint=long, encrypted_data=bytes, })
```

