---
title: help.getRecentMeUrls
description: help.getRecentMeUrls parameters, return type and example
---
## Method: help.getRecentMeUrls  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|referer|[string](../types/string.md) | Yes|


### Return type: [help\_RecentMeUrls](../types/help_RecentMeUrls.md)

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

$help_RecentMeUrls = $MadelineProto->help->getRecentMeUrls(['referer' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - help.getRecentMeUrls
* params - `{"referer": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/help.getRecentMeUrls`

Parameters:

referer - Json encoded string




Or, if you're into Lua:

```
help_RecentMeUrls = help.getRecentMeUrls({referer='string', })
```

