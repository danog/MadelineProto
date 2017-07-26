---
title: phone.requestCall
description: phone.requestCall parameters, return type and example
---
## Method: phone.requestCall  
[Back to methods index](index.md)


*You cannot use this method directly, see https://daniil.it/MadelineProto#calls for more info on handling calls*




### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|user\_id|[InputUser](../types/InputUser.md) | Yes|
|g\_a|[bytes](../types/bytes.md) | Yes|
|protocol|[PhoneCallProtocol](../types/PhoneCallProtocol.md) | Yes|


### Return type: [phone\_PhoneCall](../types/phone_PhoneCall.md)

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

$phone_PhoneCall = $MadelineProto->phone->requestCall(['user_id' => InputUser, 'g_a' => 'bytes', 'protocol' => PhoneCallProtocol, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - phone.requestCall
* params - `{"user_id": InputUser, "g_a": "bytes", "protocol": PhoneCallProtocol, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/phone.requestCall`

Parameters:

user_id - Json encoded InputUser
g_a - Json encoded bytes
protocol - Json encoded PhoneCallProtocol



Or, if you're into Lua:

```
phone_PhoneCall = phone.requestCall({user_id=InputUser, g_a='bytes', protocol=PhoneCallProtocol, })
```

