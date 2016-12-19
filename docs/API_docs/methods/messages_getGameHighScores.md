## Method: messages\_getGameHighScores  

### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|peer|[InputPeer](../types/InputPeer.md) | Required|
|id|[int](../types/int.md) | Required|
|user\_id|[InputUser](../types/InputUser.md) | Required|


### Return type: [messages\_HighScores](../types/messages_HighScores.md)

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

$messages_HighScores = $MadelineProto->messages_getGameHighScores(['peer' => InputPeer, 'id' => int, 'user_id' => InputUser, ]);
```