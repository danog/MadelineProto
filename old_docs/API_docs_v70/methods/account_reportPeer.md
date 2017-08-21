---
title: account.reportPeer
description: account.reportPeer parameters, return type and example
---
## Method: account.reportPeer  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputPeer](../types/InputPeer.md) | Yes|
|reason|[ReportReason](../types/ReportReason.md) | Yes|


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

$Bool = $MadelineProto->account->reportPeer(['peer' => InputPeer, 'reason' => ReportReason, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - account.reportPeer
* params - `{"peer": InputPeer, "reason": ReportReason, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.reportPeer`

Parameters:

peer - Json encoded InputPeer

reason - Json encoded ReportReason




Or, if you're into Lua:

```
Bool = account.reportPeer({peer=InputPeer, reason=ReportReason, })
```

