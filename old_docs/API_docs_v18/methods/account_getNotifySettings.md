---
title: account.getNotifySettings
description: account.getNotifySettings parameters, return type and example
---
## Method: account.getNotifySettings  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputNotifyPeer](../types/InputNotifyPeer.md) | Yes|


### Return type: [PeerNotifySettings](../types/PeerNotifySettings.md)

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

$PeerNotifySettings = $MadelineProto->account->getNotifySettings(['peer' => InputNotifyPeer, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - account.getNotifySettings
* params - `{"peer": InputNotifyPeer, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.getNotifySettings`

Parameters:

peer - Json encoded InputNotifyPeer




Or, if you're into Lua:

```
PeerNotifySettings = account.getNotifySettings({peer=InputNotifyPeer, })
```

