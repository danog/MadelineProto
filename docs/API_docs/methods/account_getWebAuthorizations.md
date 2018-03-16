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

