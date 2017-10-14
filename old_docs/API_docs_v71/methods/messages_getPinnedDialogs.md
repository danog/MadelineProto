---
title: messages.getPinnedDialogs
description: messages.getPinnedDialogs parameters, return type and example
---
## Method: messages.getPinnedDialogs  
[Back to methods index](index.md)




### Return type: [messages\_PeerDialogs](../types/messages_PeerDialogs.md)

### Can bots use this method: **NO**


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$messages_PeerDialogs = $MadelineProto->messages->getPinnedDialogs();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getPinnedDialogs`

Parameters:




Or, if you're into Lua:

```
messages_PeerDialogs = messages.getPinnedDialogs({})
```

