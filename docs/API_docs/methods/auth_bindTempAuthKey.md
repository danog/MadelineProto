## Method: auth\_bindTempAuthKey  

### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|perm\_auth\_key\_id|[long](../types/long.md) | Required|
|nonce|[long](../types/long.md) | Required|
|expires\_at|[int](../types/int.md) | Required|
|encrypted\_message|[bytes](../types/bytes.md) | Required|


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

$Bool = $MadelineProto->auth_bindTempAuthKey(['perm_auth_key_id' => long, 'nonce' => long, 'expires_at' => int, 'encrypted_message' => bytes, ]);
```