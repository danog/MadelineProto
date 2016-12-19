## Method: messages\_getBotCallbackAnswer  

### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|game|[Bool](../types/Bool.md) | Optional|
|peer|[InputPeer](../types/InputPeer.md) | Required|
|msg\_id|[int](../types/int.md) | Required|
|data|[bytes](../types/bytes.md) | Optional|


### Return type: [messages\_BotCallbackAnswer](../types/messages_BotCallbackAnswer.md)

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

$messages_BotCallbackAnswer = $MadelineProto->messages_getBotCallbackAnswer(['game' => Bool, 'peer' => InputPeer, 'msg_id' => int, 'data' => bytes, ]);
```