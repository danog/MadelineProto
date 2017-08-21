---
title: help.getNearestDc
description: help.getNearestDc parameters, return type and example
---
## Method: help.getNearestDc  
[Back to methods index](index.md)




### Return type: [NearestDc](../types/NearestDc.md)

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

$NearestDc = $MadelineProto->help->getNearestDc();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - help.getNearestDc
* params - `{}`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/help.getNearestDc`

Parameters:




Or, if you're into Lua:

```
NearestDc = help.getNearestDc({})
```

