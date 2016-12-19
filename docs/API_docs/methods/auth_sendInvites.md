## Method: auth\_sendInvites  

### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|phone\_numbers|Array of [string](../types/string.md) | Required|
|message|[string](../types/string.md) | Required|


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

$Bool = $MadelineProto->auth_sendInvites(['phone_numbers' => [string], 'message' => string, ]);
```