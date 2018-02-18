---
title: destroy_auth_key
description: destroy_auth_key parameters, return type and example
---
## Method: destroy\_auth\_key  
[Back to methods index](index.md)




### Return type: [DestroyAuthKeyRes](../types/DestroyAuthKeyRes.md)

### Can bots use this method: **YES**


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$DestroyAuthKeyRes = $MadelineProto->destroy_auth_key();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - destroy_auth_key
* params - `{}`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/destroy_auth_key`

Parameters:




Or, if you're into Lua:

```
DestroyAuthKeyRes = destroy_auth_key({})
```

