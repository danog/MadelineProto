---
title: photos.uploadProfilePhoto
description: photos.uploadProfilePhoto parameters, return type and example
---
## Method: photos.uploadProfilePhoto  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|file|[File path or InputFile](../types/InputFile.md) | Yes|


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
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

// !!! This API id/API hash combination will not work !!!
// !!! You must get your own @ my.telegram.org !!!
$api_id = 0;
$api_hash = '';

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['app_info' => ['api_id' => $api_id, 'api_hash' => $api_hash]]);
$MadelineProto->start();

$photos_Photo = $MadelineProto->photos->uploadProfilePhoto(['file' => InputFile, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/photos.uploadProfilePhoto`

Parameters:

file - Json encoded InputFile




Or, if you're into Lua:

```
photos_Photo = photos.uploadProfilePhoto({file=InputFile, })
```

