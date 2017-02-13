---
title: http_wait
description: http_wait parameters, return type and example
---
## Method: http\_wait  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|max\_delay|[int](../types/int.md) | Required|
|wait\_after|[int](../types/int.md) | Required|
|max\_wait|[int](../types/int.md) | Required|


### Return type: [HttpWait](../types/HttpWait.md)

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

$HttpWait = $MadelineProto->http->wait(['max_delay' => int, 'wait_after' => int, 'max_wait' => int, ]);
```
