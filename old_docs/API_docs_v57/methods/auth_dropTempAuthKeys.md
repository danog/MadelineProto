---
title: auth.dropTempAuthKeys
description: auth.dropTempAuthKeys parameters, return type and example
---
## Method: auth.dropTempAuthKeys  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|except\_auth\_keys|Array of [long](../types/long.md) | Yes|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->auth->dropTempAuthKeys(['except_auth_keys' => [long], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - auth.dropTempAuthKeys
* params - `{"except_auth_keys": [long], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/auth.dropTempAuthKeys`

Parameters:

except_auth_keys - Json encoded  array of long




Or, if you're into Lua:

```
Bool = auth.dropTempAuthKeys({except_auth_keys={long}, })
```

