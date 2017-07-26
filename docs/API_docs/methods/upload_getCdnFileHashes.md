---
title: upload.getCdnFileHashes
description: upload.getCdnFileHashes parameters, return type and example
---
## Method: upload.getCdnFileHashes  
[Back to methods index](index.md)


*You cannot use this method directly, use the upload, download_to_stream, download_to_file, download_to_dir methods instead; see https://daniil.it/MadelineProto for more info*




### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|file\_token|[bytes](../types/bytes.md) | Yes|
|offset|[int](../types/int.md) | Yes|


### Return type: [Vector\_of\_CdnFileHash](../types/CdnFileHash.md)

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

$Vector_of_CdnFileHash = $MadelineProto->upload->getCdnFileHashes(['file_token' => 'bytes', 'offset' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - upload.getCdnFileHashes
* params - `{"file_token": "bytes", "offset": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/upload.getCdnFileHashes`

Parameters:

file_token - Json encoded bytes
offset - Json encoded int



Or, if you're into Lua:

```
Vector_of_CdnFileHash = upload.getCdnFileHashes({file_token='bytes', offset=int, })
```

