---
title: account.getTmpPassword
description: Get temporary password for buying products through bots
---
## Method: account.getTmpPassword  
[Back to methods index](index.md)


Get temporary password for buying products through bots

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|password\_hash|[bytes](../types/bytes.md) | Yes|The password hash|
|period|[int](../types/int.md) | Yes|The validity period|


### Return type: [account\_TmpPassword](../types/account_TmpPassword.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$account_TmpPassword = $MadelineProto->account->getTmpPassword(['password_hash' => 'bytes', 'period' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.getTmpPassword`

Parameters:

password_hash - Json encoded bytes

period - Json encoded int




Or, if you're into Lua:

```
account_TmpPassword = account.getTmpPassword({password_hash='bytes', period=int, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PASSWORD_HASH_INVALID|The provided password hash is invalid|
|TMP_PASSWORD_DISABLED|The temporary password is disabled|


