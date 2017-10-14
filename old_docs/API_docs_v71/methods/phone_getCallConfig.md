---
title: phone.getCallConfig
description: phone.getCallConfig parameters, return type and example
---
## Method: phone.getCallConfig  
[Back to methods index](index.md)




### Return type: [DataJSON](../types/DataJSON.md)

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

$DataJSON = $MadelineProto->phone->getCallConfig();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/phone.getCallConfig`

Parameters:




Or, if you're into Lua:

```
DataJSON = phone.getCallConfig({})
```

