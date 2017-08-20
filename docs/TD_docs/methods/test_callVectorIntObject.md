---
title: test.callVectorIntObject
description: test.callVectorIntObject parameters, return type and example
---
## Method: test.callVectorIntObject  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|x|Array of [test\_Int](../types/test_Int.md) | Yes|


### Return type: [test\_VectorIntObject](../types/test_VectorIntObject.md)

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

$test_VectorIntObject = $MadelineProto->test->callVectorIntObject(['x' => [test_Int], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - test.callVectorIntObject
* params - `{"x": [test_Int], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/test.callVectorIntObject`

Parameters:

x - Json encoded  array of test_Int




Or, if you're into Lua:

```
test_VectorIntObject = test.callVectorIntObject({x={test_Int}, })
```

