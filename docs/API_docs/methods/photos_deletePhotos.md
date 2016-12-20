---
title: photos_deletePhotos
description: photos_deletePhotos parameters, return type and example
---
## Method: photos\_deletePhotos  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|Array of [InputPhoto](../types/InputPhoto.md) | Required|


### Return type: [Vector\_of\_long](../types/long.md)

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

$Vector_of_long = $MadelineProto->photos_deletePhotos(['id' => [InputPhoto], ]);
```