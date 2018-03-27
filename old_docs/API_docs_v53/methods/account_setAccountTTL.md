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
|ttl|[AccountDaysTTL](../types/AccountDaysTTL.md) | Yes|Time To Live of account|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Bool = $MadelineProto->account->setAccountTTL(['ttl' => AccountDaysTTL, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.setAccountTTL`

Parameters:

ttl - Json encoded AccountDaysTTL




Or, if you're into Lua:

```
Bool = account.setAccountTTL({ttl=AccountDaysTTL, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|TTL_DAYS_INVALID|The provided TTL is invalid|


