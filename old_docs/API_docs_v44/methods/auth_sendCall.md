---
title: auth.sendCall
description: auth.sendCall parameters, return type and example
---
## Method: auth.sendCall  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|phone\_number|[string](../types/string.md) | Yes|
|phone\_code\_hash|[string](../types/string.md) | Yes|


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

$Bool = $MadelineProto->auth->sendCall(['phone_number' => 'string', 'phone_code_hash' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - auth.sendCall
* params - `{"phone_number": "string", "phone_code_hash": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/auth.sendCall`

Parameters:

phone_number - Json encoded string

phone_code_hash - Json encoded string




Or, if you're into Lua:

```
Bool = auth.sendCall({phone_number='string', phone_code_hash='string', })
```

