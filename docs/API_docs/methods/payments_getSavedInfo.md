---
title: payments.getSavedInfo
description: payments.getSavedInfo parameters, return type and example
---
## Method: payments.getSavedInfo  
[Back to methods index](index.md)




### Return type: [payments\_SavedInfo](../types/payments_SavedInfo.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $this->bot_login($token);
}
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

Or, if you're into Lua:

```
payments_SavedInfo = payments.getSavedInfo({})
```

