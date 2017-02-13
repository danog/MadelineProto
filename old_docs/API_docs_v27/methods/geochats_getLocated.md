---
title: geochats.getLocated
description: geochats.getLocated parameters, return type and example
---
## Method: geochats.getLocated  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|geo\_point|[InputGeoPoint](../types/InputGeoPoint.md) | Required|
|radius|[int](../types/int.md) | Required|
|limit|[int](../types/int.md) | Required|


### Return type: [geochats\_Located](../types/geochats_Located.md)

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

$geochats_Located = $MadelineProto->geochats->getLocated(['geo_point' => InputGeoPoint, 'radius' => int, 'limit' => int, ]);
```
