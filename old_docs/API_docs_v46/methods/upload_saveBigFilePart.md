---
title: upload.saveBigFilePart
description: upload.saveBigFilePart parameters, return type and example
---
## Method: upload.saveBigFilePart  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|file\_id|[long](../types/long.md) | Yes|
|file\_part|[int](../types/int.md) | Yes|
|file\_total\_parts|[int](../types/int.md) | Yes|
|bytes|[bytes](../types/bytes.md) | Yes|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->upload->saveBigFilePart(['file_id' => long, 'file_part' => int, 'file_total_parts' => int, 'bytes' => 'bytes', ]);
```

Or, if you're using [PWRTelegram](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - upload.saveBigFilePart
* params - {"file_id": long, "file_part": int, "file_total_parts": int, "bytes": "bytes", }



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/upload.saveBigFilePart`

Parameters:

file_id - Json encoded long
file_part - Json encoded int
file_total_parts - Json encoded int
bytes - Json encoded bytes


```

Or, if you're into Lua:

```
Bool = upload.saveBigFilePart({file_id=long, file_part=int, file_total_parts=int, bytes='bytes', })
```

