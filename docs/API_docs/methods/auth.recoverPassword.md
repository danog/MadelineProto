## Method: auth.recoverPassword  

### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|code|[string](../types/string.md) | Required|


### Return type: [auth\_Authorization](../types/auth\_Authorization.md)

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

$auth_Authorization = $MadelineProto->auth->recoverPassword(['code' => string, ]);
```