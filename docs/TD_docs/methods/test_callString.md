---
title: test.callString
description: test.callString parameters, return type and example
---
## Method: test.callString  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|x|[string](../types/string.md) | Yes|


### Return type: [test\_String](../types/test_String.md)

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

$test_String = $MadelineProto->test->callString(['x' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - test.callString
* params - `{"x": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/test.callString`

Parameters:

x - Json encoded string




Or, if you're into Lua:

```
test_String = test.callString({x='string', })
```

