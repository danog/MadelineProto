---
title: account.deleteAccount
description: Delete this account
---
## Method: account.deleteAccount  
[Back to methods index](index.md)


Delete this account

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|reason|[CLICK ME string](../types/string.md) | Yes|Why are you going away? :(|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### MadelineProto Example:


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

$Bool = $MadelineProto->account->deleteAccount(['reason' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.deleteAccount`

Parameters:

reason - Json encoded string




Or, if you're into Lua:

```
Bool = account.deleteAccount({reason='string', })
```

