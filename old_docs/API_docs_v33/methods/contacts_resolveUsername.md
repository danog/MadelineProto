---
title: contacts.resolveUsername
description: contacts.resolveUsername parameters, return type and example
---
## Method: contacts.resolveUsername  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|username|[string](../types/string.md) | Yes|


### Return type: [User](../types/User.md)

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

$User = $MadelineProto->contacts->resolveUsername(['username' => string, ]);
```

Or, if you're using [PWRTelegram](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - contacts.resolveUsername
* params - {"username":"string"}

```

### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.resolveUsername`

Parameters:

username - Json encoded string


```

Or, if you're into Lua:

```
User = contacts.resolveUsername({username=string, })
```

