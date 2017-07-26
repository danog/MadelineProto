---
title: contacts.resolveUsername
description: contacts.resolveUsername parameters, return type and example
---
## Method: contacts.resolveUsername  
[Back to methods index](index.md)


*You cannot use this method directly, use the resolve_username, get_pwr_chat, get_info, get_full_info methods instead (see https://daniil.it/MadelineProto for more info)*




### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|username|[string](../types/string.md) | Yes|


### Return type: [contacts\_ResolvedPeer](../types/contacts_ResolvedPeer.md)

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

$contacts_ResolvedPeer = $MadelineProto->contacts->resolveUsername(['username' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - contacts.resolveUsername
* params - `{"username": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.resolveUsername`

Parameters:

username - Json encoded string



Or, if you're into Lua:

```
contacts_ResolvedPeer = contacts.resolveUsername({username='string', })
```

