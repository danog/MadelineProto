## Method: messages.getDhConfig  

### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|version|[int](../types/int.md) | Required|
|random\_length|[int](../types/int.md) | Required|


### Return type: [messages\_DhConfig](../types/messages_DhConfig.md)

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

$messages_DhConfig = $MadelineProto->messages->getDhConfig(['version' => int, 'random_length' => int, ]);
```