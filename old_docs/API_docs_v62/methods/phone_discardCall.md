---
title: phone.discardCall
description: phone.discardCall parameters, return type and example
---
## Method: phone.discardCall  
[Back to methods index](index.md)


*You cannot use this method directly, see https://daniil.it/MadelineProto#calls for more info on handling calls*




### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|peer|[InputPhoneCall](../types/InputPhoneCall.md) | Yes|
|duration|[int](../types/int.md) | Yes|
|reason|[PhoneCallDiscardReason](../types/PhoneCallDiscardReason.md) | Yes|
|connection\_id|[long](../types/long.md) | Yes|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->phone->discardCall(['peer' => InputPhoneCall, 'duration' => int, 'reason' => PhoneCallDiscardReason, 'connection_id' => long, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - phone.discardCall
* params - `{"peer": InputPhoneCall, "duration": int, "reason": PhoneCallDiscardReason, "connection_id": long, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/phone.discardCall`

Parameters:

peer - Json encoded InputPhoneCall
duration - Json encoded int
reason - Json encoded PhoneCallDiscardReason
connection_id - Json encoded long



Or, if you're into Lua:

```
Bool = phone.discardCall({peer=InputPhoneCall, duration=int, reason=PhoneCallDiscardReason, connection_id=long, })
```

