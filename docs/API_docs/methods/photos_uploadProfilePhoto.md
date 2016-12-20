---
title: photos_uploadProfilePhoto
---
## Method: photos\_uploadProfilePhoto  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|file|[InputFile](../types/InputFile.md) | Required|


### Return type: [photos\_Photo](../types/photos_Photo.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$photos_Photo = $MadelineProto->photos_uploadProfilePhoto(['file' => InputFile, ]);
```