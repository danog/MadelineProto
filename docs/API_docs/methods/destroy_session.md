---
title: destroy_session
description: destroy_session parameters, return type and example
---
## Method: destroy\_session  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|session\_id|[long](../types/long.md) | Required|


### Return type: [DestroySessionRes](../types/DestroySessionRes.md)

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

$DestroySessionRes = $MadelineProto->destroy->session(['session_id' => long, ]);
```