---
title: auth.dropTempAuthKeys
description: auth.dropTempAuthKeys parameters, return type and example
---
## Method: auth.dropTempAuthKeys  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|except\_auth\_keys|Array of [long](../types/long.md) | Yes|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->auth->dropTempAuthKeys(['except_auth_keys' => [long], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - auth.dropTempAuthKeys
* params - `{"except_auth_keys": [long], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/auth.dropTempAuthKeys`

Parameters:

except_auth_keys - Json encoded  array of long




Or, if you're into Lua:

```
Bool = auth.dropTempAuthKeys({except_auth_keys={long}, })
```

