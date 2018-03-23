---
title: photos.getUserPhotos
description: Get the profile photos of a user
---
## Method: photos.getUserPhotos  
[Back to methods index](index.md)


Get the profile photos of a user

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|user\_id|[Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|The user|
|offset|[CLICK ME int](../types/int.md) | Yes|Offset|
|max\_id|[CLICK ME int](../types/int.md) | Yes||
|limit|[CLICK ME int](../types/int.md) | Yes|Number of photos to return|


### Return type: [photos\_Photos](../types/photos_Photos.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MAX_ID_INVALID|The provided max ID is invalid|
|USER_ID_INVALID|The provided user ID is invalid|


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

$photos_Photos = $MadelineProto->photos->getUserPhotos(['user_id' => InputUser, 'offset' => int, 'max_id' => int, 'limit' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - photos.getUserPhotos
* params - `{"user_id": InputUser, "offset": int, "max_id": int, "limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/photos.getUserPhotos`

Parameters:

user_id - Json encoded InputUser

offset - Json encoded int

max_id - Json encoded int

limit - Json encoded int




Or, if you're into Lua:

```
photos_Photos = photos.getUserPhotos({user_id=InputUser, offset=int, max_id=int, limit=int, })
```

