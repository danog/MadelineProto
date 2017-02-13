---
title: geochats.createGeoChat
description: geochats.createGeoChat parameters, return type and example
---
## Method: geochats.createGeoChat  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|title|[string](../types/string.md) | Required|
|geo\_point|[InputGeoPoint](../types/InputGeoPoint.md) | Required|
|address|[string](../types/string.md) | Required|
|venue|[string](../types/string.md) | Required|


### Return type: [geochats\_StatedMessage](../types/geochats_StatedMessage.md)

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

$geochats_StatedMessage = $MadelineProto->geochats->createGeoChat(['title' => string, 'geo_point' => InputGeoPoint, 'address' => string, 'venue' => string, ]);
```
