---
title: test.callVectorString
description: test.callVectorString parameters, return type and example
---
## Method: test.callVectorString  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|x|Array of [string](../types/string.md) | Yes|


### Return type: [test\_VectorString](../types/test_VectorString.md)

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

$test_VectorString = $MadelineProto->test->callVectorString(['x' => [string], ]);
```

Or, if you're into Lua:

```
test_VectorString = test.callVectorString({x={string}, })
```

