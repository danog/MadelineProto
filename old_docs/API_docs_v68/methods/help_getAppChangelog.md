---
title: help.getAppChangelog
description: help.getAppChangelog parameters, return type and example
---
## Method: help.getAppChangelog  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|prev\_app\_version|[string](../types/string.md) | Yes|


### Return type: [Updates](../types/Updates.md)

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

$Updates = $MadelineProto->help->getAppChangelog(['prev_app_version' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/help.getAppChangelog`

Parameters:

prev_app_version - Json encoded string




Or, if you're into Lua:

```
Updates = help.getAppChangelog({prev_app_version='string', })
```

