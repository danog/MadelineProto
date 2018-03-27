---
title: payments.clearSavedInfo
description: Clear saved payments info
---
## Method: payments.clearSavedInfo  
[Back to methods index](index.md)


Clear saved payments info

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|credentials|[Bool](../types/Bool.md) | Optional|Clear credentials?|
|info|[Bool](../types/Bool.md) | Optional|Clear payment info?|


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

$Bool = $MadelineProto->payments->clearSavedInfo(['credentials' => Bool, 'info' => Bool, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/payments.clearSavedInfo`

Parameters:

credentials - Json encoded Bool

info - Json encoded Bool




Or, if you're into Lua:

```
Bool = payments.clearSavedInfo({credentials=Bool, info=Bool, })
```

