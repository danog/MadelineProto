---
title: messages_getPeerDialogs
description: messages_getPeerDialogs parameters, return type and example
---
## Method: messages\_getPeerDialogs  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|peers|Array of [InputPeer](../types/InputPeer.md) | Required|


### Return type: [messages\_PeerDialogs](../types/messages_PeerDialogs.md)

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

$messages_PeerDialogs = $MadelineProto->messages_getPeerDialogs(['peers' => [InputPeer], ]);
```