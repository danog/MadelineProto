---
title: test.callVectorStringObject
description: test.callVectorStringObject parameters, return type and example
---
## Method: test.callVectorStringObject  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|x|Array of [test\_String](../types/test_String.md) | Yes|


### Return type: [test\_VectorStringObject](../types/test_VectorStringObject.md)

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

$test_VectorStringObject = $MadelineProto->test->callVectorStringObject(['x' => [test_String], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - test.callVectorStringObject
* params - `{"x": [test_String], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/test.callVectorStringObject`

Parameters:

x - Json encoded  array of test_String




Or, if you're into Lua:

```
test_VectorStringObject = test.callVectorStringObject({x={test_String}, })
```

