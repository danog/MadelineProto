---
title: test.squareInt
description: test.squareInt parameters, return type and example
---
## Method: test.squareInt  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|x|[int](../types/int.md) | Yes|


### Return type: [test\_Int](../types/test_Int.md)

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

$test_Int = $MadelineProto->test->squareInt(['x' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - test.squareInt
* params - `{"x": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/test.squareInt`

Parameters:

x - Json encoded int




Or, if you're into Lua:

```
test_Int = test.squareInt({x=int, })
```

