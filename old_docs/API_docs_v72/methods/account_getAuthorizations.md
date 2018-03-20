---
title: account.getAuthorizations
description: Get all logged-in authorizations
---
## Method: account.getAuthorizations  
[Back to methods index](index.md)


Get all logged-in authorizations



### Return type: [account\_Authorizations](../types/account_Authorizations.md)

### Can bots use this method: **NO**


### Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

// !!! This API id/API hash combination will not work !!!
// !!! You must get your own @ my.telegram.org !!!
$api_id = 0;
$api_hash = '';

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['app_info' => ['api_id' => $api_id, 'api_hash' => $api_hash]]);
$MadelineProto->start();

$account_Authorizations = $MadelineProto->account->getAuthorizations();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.getAuthorizations`

Parameters:




Or, if you're into Lua:

```
account_Authorizations = account.getAuthorizations({})
```

