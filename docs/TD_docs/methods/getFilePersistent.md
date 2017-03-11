---
title: getFilePersistent
description: Returns information about a file by its persistent id, offline request
---
## Method: getFilePersistent  
[Back to methods index](index.md)


Returns information about a file by its persistent id, offline request

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|persistent\_file\_id|[string](../types/string.md) | Yes|Persistent identifier of the file to get|


### Return type: [File](../types/File.md)

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

$File = $MadelineProto->getFilePersistent(['persistent_file_id' => string, ]);
```

Or, if you're into Lua:

```
File = getFilePersistent({persistent_file_id=string, })
```

