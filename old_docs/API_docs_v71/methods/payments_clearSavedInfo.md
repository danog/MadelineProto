---
title: payments.clearSavedInfo
description: payments.clearSavedInfo parameters, return type and example
---
## Method: payments.clearSavedInfo  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|credentials|[Bool](../types/Bool.md) | Optional|
|info|[Bool](../types/Bool.md) | Optional|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->payments->clearSavedInfo(['credentials' => Bool, 'info' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/payments.clearSavedInfo`

Parameters:

credentials - Json encoded Bool

info - Json encoded Bool




Or, if you're into Lua:

```
Bool = payments.clearSavedInfo({credentials=Bool, info=Bool, })
```

