---
title: account.setPassword
description: account.setPassword parameters, return type and example
---
## Method: account.setPassword  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|current\_password\_hash|[CLICK ME bytes](../types/bytes.md) | Yes|
|new\_salt|[CLICK ME bytes](../types/bytes.md) | Yes|
|new\_password\_hash|[CLICK ME bytes](../types/bytes.md) | Yes|
|hint|[CLICK ME string](../types/string.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


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

$Bool = $MadelineProto->account->setPassword(['current_password_hash' => 'bytes', 'new_salt' => 'bytes', 'new_password_hash' => 'bytes', 'hint' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - account.setPassword
* params - `{"current_password_hash": "bytes", "new_salt": "bytes", "new_password_hash": "bytes", "hint": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.setPassword`

Parameters:

current_password_hash - Json encoded bytes

new_salt - Json encoded bytes

new_password_hash - Json encoded bytes

hint - Json encoded string




Or, if you're into Lua:

```
Bool = account.setPassword({current_password_hash='bytes', new_salt='bytes', new_password_hash='bytes', hint='string', })
```

