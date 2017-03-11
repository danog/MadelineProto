---
title: test.forceGetDifference
description: test.forceGetDifference parameters, return type and example
---
## Method: test.forceGetDifference  
[Back to methods index](index.md)




### Return type: [Ok](../types/Ok.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
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

