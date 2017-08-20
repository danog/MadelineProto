---
title: getFilePersistent
description: Returns information about a file by its persistent id, offline request
---
## Method: getFilePersistent  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns information about a file by its persistent id, offline request

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|persistent\_file\_id|[string](../types/string.md) | Yes|Persistent identifier of the file to get|


### Return type: [File](../types/File.md)

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

$File = $MadelineProto->getFilePersistent(['persistent_file_id' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - getFilePersistent
* params - `{"persistent_file_id": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/getFilePersistent`

Parameters:

persistent_file_id - Json encoded string




Or, if you're into Lua:

```
File = getFilePersistent({persistent_file_id='string', })
```

