---
title: photos.deletePhotos
description: Delete profile photos
---
## Method: photos.deletePhotos  
[Back to methods index](index.md)


Delete profile photos

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|Array of [MessageMedia, Update, Message or InputPhoto](../types/InputPhoto.md) | Yes|The profile photos to delete|


### Return type: [Vector\_of\_long](../types/long.md)

### Can bots use this method: **NO**


### MadelineProto Example:


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

$Vector_of_long = $MadelineProto->photos->deletePhotos(['id' => [InputPhoto, InputPhoto], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/photos.deletePhotos`

Parameters:

id - Json encoded  array of InputPhoto




Or, if you're into Lua:

```
Vector_of_long = photos.deletePhotos({id={InputPhoto}, })
```

