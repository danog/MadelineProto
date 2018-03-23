---
title: account.resetWebAuthorizations
description: Reset all telegram web login authorizations
---
## Method: account.resetWebAuthorizations  
[Back to methods index](index.md)


Reset all telegram web login authorizations



### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Bool = $MadelineProto->account->resetWebAuthorizations();
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - account.resetWebAuthorizations
* params - `{}`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.resetWebAuthorizations`

Parameters:




Or, if you're into Lua:

```
Bool = account.resetWebAuthorizations({})
```

