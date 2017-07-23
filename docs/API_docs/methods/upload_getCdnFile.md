---
title: upload.getCdnFile
description: upload.getCdnFile parameters, return type and example
---
## Method: upload.getCdnFile  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|file\_token|[bytes](../types/bytes.md) | Yes|
|offset|[int](../types/int.md) | Yes|
|limit|[int](../types/int.md) | Yes|


### Return type: [upload\_CdnFile](../types/upload_CdnFile.md)

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

$upload_CdnFile = $MadelineProto->upload->getCdnFile(['file_token' => 'bytes', 'offset' => int, 'limit' => int, ]);
```

Or, if you're using [PWRTelegram](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - upload.getCdnFile
* params - {"file_token": "bytes", "offset": int, "limit": int, }

```

### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/upload.getCdnFile`

Parameters:

file_token - Json encoded bytes
offset - Json encoded int
limit - Json encoded int


```

Or, if you're into Lua:

```
upload_CdnFile = upload.getCdnFile({file_token='bytes', offset=int, limit=int, })
```

