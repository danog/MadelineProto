---
title: deleteAccount
description: Deletes the account of currently logged in user, deleting from the server all information associated with it. Account's phone number can be used to create new account, but only once in two weeks
---
## Method: deleteAccount  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Deletes the account of currently logged in user, deleting from the server all information associated with it. Account's phone number can be used to create new account, but only once in two weeks

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|reason|[string](../types/string.md) | Yes|Optional reason of account deletion|


### Return type: [Ok](../types/Ok.md)

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

$Ok = $MadelineProto->deleteAccount(['reason' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - deleteAccount
* params - `{"reason": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/deleteAccount`

Parameters:

reason - Json encoded string




Or, if you're into Lua:

```
Ok = deleteAccount({reason='string', })
```

