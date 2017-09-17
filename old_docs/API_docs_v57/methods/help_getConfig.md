---
title: help.getConfig
description: help.getConfig parameters, return type and example
---
## Method: help.getConfig  
[Back to methods index](index.md)




### Return type: [Config](../types/Config.md)

### Can bots use this method: **YES**


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

$Config = $MadelineProto->help->getConfig();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - help.getConfig
* params - `{}`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/help.getConfig`

Parameters:




Or, if you're into Lua:

```
Config = help.getConfig({})
```

