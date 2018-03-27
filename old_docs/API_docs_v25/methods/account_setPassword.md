---
title: account.setPassword
description: Set 2FA password
---
## Method: account.setPassword  
[Back to methods index](index.md)


Set 2FA password

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|current\_password\_hash|[bytes](../types/bytes.md) | Yes|Use only if you have set a 2FA password: `$current_salt = $MadelineProto->account->getPassword()['current_salt']; $current_password_hash = hash('sha256', $current_salt.$password.$current_salt, true);`|
|new\_salt|[bytes](../types/bytes.md) | Yes|New salt|
|new\_password\_hash|[bytes](../types/bytes.md) | Yes|`hash('sha256', $new_salt.$new_password.$new_salt, true)`|
|hint|[string](../types/string.md) | Yes|Hint|


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

