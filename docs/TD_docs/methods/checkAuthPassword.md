---
title: checkAuthPassword
description: Checks password for correctness. Works only when authGetState returns authStateWaitPassword. Returns authStateOk on success
---
## Method: checkAuthPassword  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Checks password for correctness. Works only when authGetState returns authStateWaitPassword. Returns authStateOk on success

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|password|[string](../types/string.md) | Yes|Password to check|


### Return type: [AuthState](../types/AuthState.md)

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

$AuthState = $MadelineProto->checkAuthPassword(['password' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - checkAuthPassword
* params - `{"password": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/checkAuthPassword`

Parameters:

password - Json encoded string




Or, if you're into Lua:

```
AuthState = checkAuthPassword({password='string', })
```

