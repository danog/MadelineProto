---
title: photos.uploadProfilePhoto
description: photos.uploadProfilePhoto parameters, return type and example
---
## Method: photos.uploadProfilePhoto  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|file|[InputFile](../types/InputFile.md) | Yes|
|caption|[string](../types/string.md) | Yes|
|geo\_point|[InputGeoPoint](../types/InputGeoPoint.md) | Yes|
|crop|[InputPhotoCrop](../types/InputPhotoCrop.md) | Yes|


### Return type: [photos\_Photo](../types/photos_Photo.md)

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

$photos_Photo = $MadelineProto->photos->uploadProfilePhoto(['file' => InputFile, 'caption' => string, 'geo_point' => InputGeoPoint, 'crop' => InputPhotoCrop, ]);
```

Or, if you're into Lua:

```
photos_Photo = photos.uploadProfilePhoto({file=InputFile, caption=string, geo_point=InputGeoPoint, crop=InputPhotoCrop, })
```

