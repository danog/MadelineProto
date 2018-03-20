---
title: account.setAccountTTL
description: Set account TTL
---
## Method: account.setAccountTTL  
[Back to methods index](index.md)


Set account TTL

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|ttl|[CLICK ME AccountDaysTTL](../types/AccountDaysTTL.md) | Yes|Time To Live of account|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|TTL_DAYS_INVALID|The provided TTL is invalid|


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

$Bool = $MadelineProto->account->setAccountTTL(['ttl' => AccountDaysTTL, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.setAccountTTL`

Parameters:

ttl - Json encoded AccountDaysTTL




Or, if you're into Lua:

```
Bool = account.setAccountTTL({ttl=AccountDaysTTL, })
```

