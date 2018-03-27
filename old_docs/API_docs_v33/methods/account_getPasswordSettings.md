---
title: account.getPasswordSettings
description: Get the current 2FA settings
---
## Method: account.getPasswordSettings  
[Back to methods index](index.md)


Get the current 2FA settings

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|current\_password\_hash|[bytes](../types/bytes.md) | Yes|Use only if you have set a 2FA password: `$current_salt = $MadelineProto->account->getPassword()['current_salt']; $current_password_hash = hash('sha256', $current_salt.$password.$current_salt, true);`|


### Return type: [account\_PasswordSettings](../types/account_PasswordSettings.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$account_PasswordSettings = $MadelineProto->account->getPasswordSettings(['current_password_hash' => 'bytes', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.getPasswordSettings`

Parameters:

current_password_hash - Json encoded bytes




Or, if you're into Lua:

```
account_PasswordSettings = account.getPasswordSettings({current_password_hash='bytes', })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PASSWORD_HASH_INVALID|The provided password hash is invalid|


