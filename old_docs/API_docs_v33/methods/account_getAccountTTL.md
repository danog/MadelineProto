---
title: account.getAccountTTL
description: Get account TTL
---
## Method: account.getAccountTTL  
[Back to methods index](index.md)


Get account TTL



### Return type: [AccountDaysTTL](../types/AccountDaysTTL.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$AccountDaysTTL = $MadelineProto->account->getAccountTTL();
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.getAccountTTL`

Parameters:




Or, if you're into Lua:

```
AccountDaysTTL = account.getAccountTTL({})
```

