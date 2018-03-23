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

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
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

