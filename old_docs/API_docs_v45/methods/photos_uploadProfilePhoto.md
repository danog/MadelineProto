---
title: photos.uploadProfilePhoto
description: photos.uploadProfilePhoto parameters, return type and example
---
## Method: photos.uploadProfilePhoto  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|file|[InputFile](../types/InputFile.md) | Yes|
|caption|[string](../types/string.md) | Yes|
|geo\_point|[InputGeoPoint](../types/InputGeoPoint.md) | Optional|
|crop|[InputPhotoCrop](../types/InputPhotoCrop.md) | Yes|


### Return type: [photos\_Photo](../types/photos_Photo.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|FILE_PARTS_INVALID|The number of file parts is invalid|
|IMAGE_PROCESS_FAILED|Failure while processing image|
|PHOTO_CROP_SIZE_SMALL|Photo is too small|
|PHOTO_EXT_INVALID|The extension of the photo is invalid|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$photos_Photo = $MadelineProto->photos->uploadProfilePhoto(['file' => InputFile, 'caption' => 'string', 'geo_point' => InputGeoPoint, 'crop' => InputPhotoCrop, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/photos.uploadProfilePhoto`

Parameters:

file - Json encoded InputFile

caption - Json encoded string

geo_point - Json encoded InputGeoPoint

crop - Json encoded InputPhotoCrop




Or, if you're into Lua:

```
photos_Photo = photos.uploadProfilePhoto({file=InputFile, caption='string', geo_point=InputGeoPoint, crop=InputPhotoCrop, })
```

