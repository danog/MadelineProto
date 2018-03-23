---
title: payments.getSavedInfo
description: Get saved payments info
---
## Method: payments.getSavedInfo  
[Back to methods index](index.md)


Get saved payments info



### Return type: [payments\_SavedInfo](../types/payments_SavedInfo.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$payments_SavedInfo = $MadelineProto->payments->getSavedInfo();
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/payments.getSavedInfo`

Parameters:




Or, if you're into Lua:

```
payments_SavedInfo = payments.getSavedInfo({})
```

