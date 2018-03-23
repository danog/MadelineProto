---
title: account.getAuthorizations
description: Get all logged-in authorizations
---
## Method: account.getAuthorizations  
[Back to methods index](index.md)


Get all logged-in authorizations



### Return type: [account\_Authorizations](../types/account_Authorizations.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$account_Authorizations = $MadelineProto->account->getAuthorizations();
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.getAuthorizations`

Parameters:




Or, if you're into Lua:

```
account_Authorizations = account.getAuthorizations({})
```

