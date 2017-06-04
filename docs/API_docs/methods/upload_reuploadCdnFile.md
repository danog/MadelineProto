---
title: upload.reuploadCdnFile
description: upload.reuploadCdnFile parameters, return type and example
---
## Method: upload.reuploadCdnFile  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|file\_token|[bytes](../types/bytes.md) | Yes|
|request\_token|[bytes](../types/bytes.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $this->bot_login($token);
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

$Bool = $MadelineProto->upload->reuploadCdnFile(['file_token' => bytes, 'request_token' => bytes, ]);
```

Or, if you're into Lua:

```
Bool = upload.reuploadCdnFile({file_token=bytes, request_token=bytes, })
```

