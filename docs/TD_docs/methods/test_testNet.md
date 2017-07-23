---
title: test.testNet
description: test.testNet parameters, return type and example
---
## Method: test.testNet  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO




### Return type: [test\_Empty](../types/test_Empty.md)

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

$test_Empty = $MadelineProto->test->testNet();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - test.testNet
* params - `{}`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/test.testNet`

Parameters:




Or, if you're into Lua:

```
test_Empty = test.testNet({})
```

