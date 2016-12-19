## Method: auth\_exportAuthorization  

### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|dc\_id|[int](../types/int.md) | Required|


### Return type: [auth\_ExportedAuthorization](../types/auth_ExportedAuthorization.md)

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

$auth_ExportedAuthorization = $MadelineProto->auth_exportAuthorization(['dc_id' => int, ]);
```