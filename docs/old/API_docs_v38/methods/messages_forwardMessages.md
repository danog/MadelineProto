---
title: messages_forwardMessages
description: messages_forwardMessages parameters, return type and example
---
## Method: messages\_forwardMessages  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|from\_peer|[InputPeer](../types/InputPeer.md) | Required|
|id|Array of [int](../types/int.md) | Required|
|to\_peer|[InputPeer](../types/InputPeer.md) | Required|


### Return type: [Updates](../types/Updates.md)

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

$Updates = $MadelineProto->messages->forwardMessages(['from_peer' => InputPeer, 'id' => [int], 'to_peer' => InputPeer, ]);
```