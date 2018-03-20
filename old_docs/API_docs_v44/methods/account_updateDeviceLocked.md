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
|period|[CLICK ME int](../types/int.md) | Yes|For how long should notifications be disabled|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


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

