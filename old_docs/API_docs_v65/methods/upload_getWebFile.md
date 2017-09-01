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
$MadelineProto = new \danog\MadelineProto\API();
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

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

