---
title: test.forceGetDifference
description: test.forceGetDifference parameters, return type and example
---
## Method: test.forceGetDifference  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO




### Return type: [Ok](../types/Ok.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
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

$Ok = $MadelineProto->test->forceGetDifference();
```

Or, if you're into Lua:

```
Ok = test.forceGetDifference({})
```

