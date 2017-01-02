---
title: invokeAfterMsgs
description: invokeAfterMsgs parameters, return type and example
---
## Method: invokeAfterMsgs  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|msg\_ids|Array of [long](../types/long.md) | Required|
|query|[!X](../types/!X.md) | Required|


### Return type: [X](../types/X.md)

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

$X = $MadelineProto->invokeAfterMsgs(['msg_ids' => [long], 'query' => !X, ]);
```