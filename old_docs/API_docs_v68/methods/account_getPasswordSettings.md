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
|current\_password\_hash|[CLICK ME bytes](../types/bytes.md) | Yes|$current_salt = $MadelineProto->account->getPassword()['current_salt']; $current_password_hash = hash('sha256', $current_salt.$password.$current_salt);|


### Return type: [account\_PasswordSettings](../types/account_PasswordSettings.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PASSWORD_HASH_INVALID|The provided password hash is invalid|


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

$account_PasswordSettings = $MadelineProto->account->getPasswordSettings(['current_password_hash' => 'bytes', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.getPasswordSettings`

Parameters:

current_password_hash - Json encoded bytes




Or, if you're into Lua:

```
account_PasswordSettings = account.getPasswordSettings({current_password_hash='bytes', })
```

