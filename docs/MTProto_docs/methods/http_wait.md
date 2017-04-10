---
title: http_wait
description: http_wait parameters, return type and example
---
## Method: http\_wait  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|max\_delay|[int](../types/int.md) | Yes|
|wait\_after|[int](../types/int.md) | Yes|
|max\_wait|[int](../types/int.md) | Yes|


### Return type: [HttpWait](../types/HttpWait.md)

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

$HttpWait = $MadelineProto->http_wait(['max_delay' => int, 'wait_after' => int, 'max_wait' => int, ]);
```

Or, if you're into Lua:

```
HttpWait = http_wait({max_delay=int, wait_after=int, max_wait=int, })
```

