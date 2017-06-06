---
title: test.callVectorInt
description: test.callVectorInt parameters, return type and example
---
## Method: test.callVectorInt  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|x|Array of [int](../types/int.md) | Yes|


### Return type: [test\_VectorInt](../types/test_VectorInt.md)

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

$test_VectorInt = $MadelineProto->test->callVectorInt(['x' => [int], ]);
```

Or, if you're into Lua:

```
test_VectorInt = test.callVectorInt({x={int}, })
```

