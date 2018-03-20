---
title: upload.getWebFile
description: upload.getWebFile parameters, return type and example
---
## Method: upload.getWebFile  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|location|[InputWebFileLocation](../types/InputWebFileLocation.md) | Yes|
|offset|[int](../types/int.md) | Yes|
|limit|[int](../types/int.md) | Yes|


### Return type: [upload\_WebFile](../types/upload_WebFile.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|LOCATION_INVALID|The provided location is invalid|


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

$upload_WebFile = $MadelineProto->upload->getWebFile(['location' => InputWebFileLocation, 'offset' => int, 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/upload.getWebFile`

Parameters:

location - Json encoded InputWebFileLocation

offset - Json encoded int

limit - Json encoded int




Or, if you're into Lua:

```
upload_WebFile = upload.getWebFile({location=InputWebFileLocation, offset=int, limit=int, })
```

