---
title: getRecoveryEmail
description: Returns set up recovery email. This method can be used to verify a password provided by the user
---
## Method: getRecoveryEmail  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns set up recovery email. This method can be used to verify a password provided by the user

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|password|[string](../types/string.md) | Yes|Current user password|


### Return type: [RecoveryEmail](../types/RecoveryEmail.md)

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

$RecoveryEmail = $MadelineProto->getRecoveryEmail(['password' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - getRecoveryEmail
* params - `{"password": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/getRecoveryEmail`

Parameters:

password - Json encoded string




Or, if you're into Lua:

```
RecoveryEmail = getRecoveryEmail({password='string', })
```

