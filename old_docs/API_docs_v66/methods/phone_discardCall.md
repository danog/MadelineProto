---
title: phone.discardCall
description: phone.discardCall parameters, return type and example
---
## Method: phone.discardCall  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|peer|[InputPhoneCall](../types/InputPhoneCall.md) | Yes|
|duration|[int](../types/int.md) | Yes|
|reason|[PhoneCallDiscardReason](../types/PhoneCallDiscardReason.md) | Yes|
|connection\_id|[long](../types/long.md) | Yes|


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

$Updates = $MadelineProto->phone->discardCall(['peer' => InputPhoneCall, 'duration' => int, 'reason' => PhoneCallDiscardReason, 'connection_id' => long, ]);
```

Or, if you're into Lua:

```
Updates = phone.discardCall({peer=InputPhoneCall, duration=int, reason=PhoneCallDiscardReason, connection_id=long, })
```

