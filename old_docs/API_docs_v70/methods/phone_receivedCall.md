---
title: phone.receivedCall
description: phone.receivedCall parameters, return type and example
---
## Method: phone.receivedCall  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputPhoneCall](../types/InputPhoneCall.md) | Yes|


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

$Bool = $MadelineProto->phone->receivedCall(['peer' => InputPhoneCall, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - phone.receivedCall
* params - `{"peer": InputPhoneCall, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/phone.receivedCall`

Parameters:

peer - Json encoded InputPhoneCall




Or, if you're into Lua:

```
Bool = phone.receivedCall({peer=InputPhoneCall, })
```

