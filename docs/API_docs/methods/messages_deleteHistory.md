---
title: messages_deleteHistory
---
## Method: messages\_deleteHistory  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|just\_clear|[Bool](../types/Bool.md) | Optional|
|peer|[InputPeer](../types/InputPeer.md) | Required|
|max\_id|[int](../types/int.md) | Required|


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

$messages_AffectedHistory = $MadelineProto->messages_deleteHistory(['just_clear' => Bool, 'peer' => InputPeer, 'max_id' => int, ]);
```