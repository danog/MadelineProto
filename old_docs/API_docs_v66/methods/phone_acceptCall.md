---
title: phone.acceptCall
description: phone.acceptCall parameters, return type and example
---
## Method: phone.acceptCall  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|peer|[InputPhoneCall](../types/InputPhoneCall.md) | Yes|
|g\_b|[bytes](../types/bytes.md) | Yes|
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

$phone_PhoneCall = $MadelineProto->phone->acceptCall(['peer' => InputPhoneCall, 'g_b' => 'bytes', 'protocol' => PhoneCallProtocol, ]);
```

Or, if you're using [PWRTelegram](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - phone.acceptCall
* params - {"peer": InputPhoneCall, "g_b": "bytes", "protocol": PhoneCallProtocol, }



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/phone.acceptCall`

Parameters:

```
peer - Json encoded InputPhoneCall
g_b - Json encoded bytes
protocol - Json encoded PhoneCallProtocol

```

Or, if you're into Lua:

```
phone_PhoneCall = phone.acceptCall({peer=InputPhoneCall, g_b='bytes', protocol=PhoneCallProtocol, })
```

