---
title: upload.getWebFile
description: Download a file through telegram
---
## Method: upload.getWebFile  
[Back to methods index](index.md)


Download a file through telegram

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|location|[InputWebFileLocation](../types/InputWebFileLocation.md) | Yes|The file|
|offset|[int](../types/int.md) | Yes|The offset in bytes|
|limit|[int](../types/int.md) | Yes|The number of bytes to fetch|


### Return type: [upload\_WebFile](../types/upload_WebFile.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$upload_WebFile = $MadelineProto->upload->getWebFile(['location' => InputWebFileLocation, 'offset' => int, 'limit' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



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

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|LOCATION_INVALID|The provided location is invalid|


