---
title: messages.forwardMessages
description: messages.forwardMessages parameters, return type and example
---
## Method: messages.forwardMessages  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|silent|[Bool](../types/Bool.md) | Optional|
|background|[Bool](../types/Bool.md) | Optional|
|from\_peer|[InputPeer](../types/InputPeer.md) | Yes|
|id|Array of [int](../types/int.md) | Yes|
|to\_peer|[InputPeer](../types/InputPeer.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Updates = $MadelineProto->messages->forwardMessages(['silent' => Bool, 'background' => Bool, 'from_peer' => InputPeer, 'id' => [int], 'to_peer' => InputPeer, ]);
```

Or, if you're into Lua:

```
Updates = messages.forwardMessages({silent=Bool, background=Bool, from_peer=InputPeer, id={int}, to_peer=InputPeer, })
```

