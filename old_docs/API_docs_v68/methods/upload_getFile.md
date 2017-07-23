---
title: upload.getFile
description: upload.getFile parameters, return type and example
---
## Method: upload.getFile  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|location|[InputFileLocation](../types/InputFileLocation.md) | Yes|
|offset|[int](../types/int.md) | Yes|
|limit|[int](../types/int.md) | Yes|


### Return type: [upload\_File](../types/upload_File.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$upload_File = $MadelineProto->upload->getFile(['location' => InputFileLocation, 'offset' => int, 'limit' => int, ]);
```

Or, if you're using [PWRTelegram](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - upload.getFile
* params - `{"location": InputFileLocation, "offset": int, "limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/upload.getFile`

Parameters:

location - Json encoded InputFileLocation
offset - Json encoded int
limit - Json encoded int



Or, if you're into Lua:

```
upload_File = upload.getFile({location=InputFileLocation, offset=int, limit=int, })
```

