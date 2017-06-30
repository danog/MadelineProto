---
title: channels.updatePinnedMessage
description: channels.updatePinnedMessage parameters, return type and example
---
## Method: channels.updatePinnedMessage  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|silent|[Bool](../types/Bool.md) | Optional|
|channel|[InputChannel](../types/InputChannel.md) | Yes|
|id|[int](../types/int.md) | Yes|


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

$Updates = $MadelineProto->channels->updatePinnedMessage(['silent' => Bool, 'channel' => InputChannel, 'id' => int, ]);
```

Or, if you're into Lua:

```
Updates = channels.updatePinnedMessage({silent=Bool, channel=InputChannel, id=int, })
```

