---
title: test.callEmpty
description: test.callEmpty parameters, return type and example
---
## Method: test.callEmpty  
[Back to methods index](index.md)




### Return type: [test\_Empty](../types/test_Empty.md)

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

$test_Empty = $MadelineProto->test->callEmpty();
```

Or, if you're into Lua:

```
test_Empty = test.callEmpty({})
```

