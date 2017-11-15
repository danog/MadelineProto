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
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
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

