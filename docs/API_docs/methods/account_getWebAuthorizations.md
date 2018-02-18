---
title: account.getWebAuthorizations
description: account.getWebAuthorizations parameters, return type and example
---
## Method: account.getWebAuthorizations  
[Back to methods index](index.md)




### Return type: [account\_WebAuthorizations](../types/account_WebAuthorizations.md)

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

$account_WebAuthorizations = $MadelineProto->account->getWebAuthorizations();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - account.getWebAuthorizations
* params - `{}`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.getWebAuthorizations`

Parameters:




Or, if you're into Lua:

```
account_WebAuthorizations = account.getWebAuthorizations({})
```

