## Method: channels.deleteUserHistory  

### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|channel|[InputChannel](../types/InputChannel.md) | Required|
|user\_id|[InputUser](../types/InputUser.md) | Required|


### Return type: [messages\_AffectedHistory](../types/messages_AffectedHistory.md)

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

$messages_AffectedHistory = $MadelineProto->channels->deleteUserHistory(['channel' => InputChannel, 'user_id' => InputUser, ]);
```