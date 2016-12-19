## Method: messages\_requestEncryption  

### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|user\_id|[InputUser](../types/InputUser.md) | Required|
|random\_id|[int](../types/int.md) | Required|
|g\_a|[bytes](../types/bytes.md) | Required|


### Return type: [EncryptedChat](../types/EncryptedChat.md)

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

$EncryptedChat = $MadelineProto->messages_requestEncryption(['user_id' => InputUser, 'random_id' => int, 'g_a' => bytes, ]);
```