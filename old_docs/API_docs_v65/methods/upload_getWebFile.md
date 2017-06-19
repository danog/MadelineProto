---
title: upload.getWebFile
description: upload.getWebFile parameters, return type and example
---
## Method: upload.getWebFile  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|location|[InputWebFileLocation](../types/InputWebFileLocation.md) | Yes|
|offset|[int](../types/int.md) | Yes|
|limit|[int](../types/int.md) | Yes|


### Return type: [upload\_WebFile](../types/upload_WebFile.md)

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

$upload_WebFile = $MadelineProto->upload->getWebFile(['location' => InputWebFileLocation, 'offset' => int, 'limit' => int, ]);
```

Or, if you're into Lua:

```
upload_WebFile = upload.getWebFile({location=InputWebFileLocation, offset=int, limit=int, })
```

