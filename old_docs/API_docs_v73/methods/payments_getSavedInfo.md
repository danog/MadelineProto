---
title: payments.getSavedInfo
description: payments.getSavedInfo parameters, return type and example
---
## Method: payments.getSavedInfo  
[Back to methods index](index.md)




### Return type: [payments\_SavedInfo](../types/payments_SavedInfo.md)

### Can bots use this method: **NO**


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$payments_SavedInfo = $MadelineProto->payments->getSavedInfo();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/payments.getSavedInfo`

Parameters:




Or, if you're into Lua:

```
payments_SavedInfo = payments.getSavedInfo({})
```

