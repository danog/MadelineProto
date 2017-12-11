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
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
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

