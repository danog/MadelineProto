---
title: photos.deletePhotos
description: photos.deletePhotos parameters, return type and example
---
## Method: photos.deletePhotos  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|Array of [MessageMedia, Update, Message or InputPhoto](../types/InputPhoto.md) | Yes|


### Return type: [Vector\_of\_long](../types/long.md)

### Can bots use this method: **NO**


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

$Vector_of_long = $MadelineProto->photos->deletePhotos(['id' => [InputPhoto, InputPhoto], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/photos.deletePhotos`

Parameters:

id - Json encoded  array of InputPhoto




Or, if you're into Lua:

```
Vector_of_long = photos.deletePhotos({id={InputPhoto}, })
```

