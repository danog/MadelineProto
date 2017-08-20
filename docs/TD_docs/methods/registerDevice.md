---
title: registerDevice
description: Registers current used device for receiving push notifications
---
## Method: registerDevice  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Registers current used device for receiving push notifications

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|device\_token|[DeviceToken](../types/DeviceToken.md) | Yes|Device token|


### Return type: [Ok](../types/Ok.md)

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

$Ok = $MadelineProto->registerDevice(['device_token' => DeviceToken, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - registerDevice
* params - `{"device_token": DeviceToken, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/registerDevice`

Parameters:

device_token - Json encoded DeviceToken




Or, if you're into Lua:

```
Ok = registerDevice({device_token=DeviceToken, })
```

