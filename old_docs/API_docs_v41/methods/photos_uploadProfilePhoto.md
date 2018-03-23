---
title: photos.uploadProfilePhoto
description: Upload profile photo
---
## Method: photos.uploadProfilePhoto  
[Back to methods index](index.md)


Upload profile photo

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|file|[File path or InputFile](../types/InputFile.md) | Yes|The photo|
|caption|[CLICK ME string](../types/string.md) | Yes|Caption type|
|geo\_point|[CLICK ME InputGeoPoint](../types/InputGeoPoint.md) | Optional|Location|
|crop|[CLICK ME InputPhotoCrop](../types/InputPhotoCrop.md) | Yes|Cropping info|


### Return type: [photos\_Photo](../types/photos_Photo.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|FILE_PARTS_INVALID|The number of file parts is invalid|
|IMAGE_PROCESS_FAILED|Failure while processing image|
|PHOTO_CROP_SIZE_SMALL|Photo is too small|
|PHOTO_EXT_INVALID|The extension of the photo is invalid|


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$photos_Photo = $MadelineProto->photos->uploadProfilePhoto(['file' => InputFile, 'caption' => 'string', 'geo_point' => InputGeoPoint, 'crop' => InputPhotoCrop, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



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

