---
title: account.setAccountTTL
description: account.setAccountTTL parameters, return type and example
---
## Method: account.setAccountTTL  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|ttl|[AccountDaysTTL](../types/AccountDaysTTL.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|TTL_DAYS_INVALID|The provided TTL is invalid|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

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

