---
title: payments.clearSavedInfo
description: payments.clearSavedInfo parameters, return type and example
---
## Method: payments.clearSavedInfo  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|credentials|[Bool](../types/Bool.md) | Optional|
|info|[Bool](../types/Bool.md) | Optional|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->payments->clearSavedInfo(['credentials' => Bool, 'info' => Bool, ]);
```

Or, if you're into Lua:

```
Bool = payments.clearSavedInfo({credentials=Bool, info=Bool, })
```

