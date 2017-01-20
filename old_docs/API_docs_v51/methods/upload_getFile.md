---
title: upload.getFile
description: upload.getFile parameters, return type and example
---
## Method: upload.getFile  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|location|[InputFileLocation](../types/InputFileLocation.md) | Required|
|offset|[int](../types/int.md) | Required|
|limit|[int](../types/int.md) | Required|


### Return type: [upload\_File](../types/upload_File.md)

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

$upload_File = $MadelineProto->upload->getFile(['location' => InputFileLocation, 'offset' => int, 'limit' => int, ]);
```