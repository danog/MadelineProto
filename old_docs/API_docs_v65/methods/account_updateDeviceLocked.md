---
title: account.updateDeviceLocked
description: Disable all notifications for a certain period
---
## Method: account.updateDeviceLocked  
[Back to methods index](index.md)


Disable all notifications for a certain period

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|period|[int](../types/int.md) | Yes|For how long should notifications be disabled|


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

$Bool = $MadelineProto->account->updateDeviceLocked(['period' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.updateDeviceLocked`

Parameters:

period - Json encoded int




Or, if you're into Lua:

```
Bool = account.updateDeviceLocked({period=int, })
```

