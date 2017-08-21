---
title: photos.uploadProfilePhoto
description: photos.uploadProfilePhoto parameters, return type and example
---
## Method: photos.uploadProfilePhoto  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|file|[InputFile](../types/InputFile.md) | Yes|


### Return type: [photos\_Photo](../types/photos_Photo.md)

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

$photos_Photo = $MadelineProto->photos->uploadProfilePhoto(['file' => InputFile, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - photos.uploadProfilePhoto
* params - `{"file": InputFile, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/photos.uploadProfilePhoto`

Parameters:

file - Json encoded InputFile




Or, if you're into Lua:

```
photos_Photo = photos.uploadProfilePhoto({file=InputFile, })
```

