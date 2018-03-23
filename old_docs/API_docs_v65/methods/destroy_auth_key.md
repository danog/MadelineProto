---
title: destroy_auth_key
description: Destroy current authorization key
---
## Method: destroy\_auth\_key  
[Back to methods index](index.md)


Destroy current authorization key



### Return type: [DestroyAuthKeyRes](../types/DestroyAuthKeyRes.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$DestroyAuthKeyRes = $MadelineProto->destroy_auth_key();
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

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

