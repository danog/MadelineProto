---
title: test.callString
description: test.callString parameters, return type and example
---
## Method: test.callString  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|x|[string](../types/string.md) | Yes|


### Return type: [test\_String](../types/test_String.md)

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

$test_String = $MadelineProto->test->callString(['x' => string, ]);
```

Or, if you're into Lua:

```
test_String = test.callString({x=string, })
```

