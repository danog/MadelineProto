---
title: photos.updateProfilePhoto
description: photos.updateProfilePhoto parameters, return type and example
---
## Method: photos.updateProfilePhoto  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[MessageMedia, Update, Message or InputPhoto](../types/InputPhoto.md) | Optional|
|crop|[CLICK ME InputPhotoCrop](../types/InputPhotoCrop.md) | Yes|


### Return type: [UserProfilePhoto](../types/UserProfilePhoto.md)

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

$UserProfilePhoto = $MadelineProto->photos->updateProfilePhoto(['id' => InputPhoto, 'crop' => InputPhotoCrop, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/photos.updateProfilePhoto`

Parameters:

id - Json encoded InputPhoto

crop - Json encoded InputPhotoCrop




Or, if you're into Lua:

```
UserProfilePhoto = photos.updateProfilePhoto({id=InputPhoto, crop=InputPhotoCrop, })
```

