---
title: account.getWebAuthorizations
description: Get telegram web login authorizations
---
## Method: account.getWebAuthorizations  
[Back to methods index](index.md)


Get telegram web login authorizations



### Return type: [account\_WebAuthorizations](../types/account_WebAuthorizations.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$account_WebAuthorizations = $MadelineProto->account->getWebAuthorizations();
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

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

