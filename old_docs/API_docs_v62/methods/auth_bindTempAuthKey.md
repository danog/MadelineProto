---
title: auth.bindTempAuthKey
description: auth.bindTempAuthKey parameters, return type and example
---
## Method: auth.bindTempAuthKey  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|perm\_auth\_key\_id|[long](../types/long.md) | Yes|
|nonce|[long](../types/long.md) | Yes|
|expires\_at|[int](../types/int.md) | Yes|
|encrypted\_message|[bytes](../types/bytes.md) | Yes|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->auth->bindTempAuthKey(['perm_auth_key_id' => long, 'nonce' => long, 'expires_at' => int, 'encrypted_message' => bytes, ]);
```

Or, if you're into Lua:

```
Bool = auth.bindTempAuthKey({perm_auth_key_id=long, nonce=long, expires_at=int, encrypted_message=bytes, })
```

