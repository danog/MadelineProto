---
title: messages.getDocumentByHash
description: messages.getDocumentByHash parameters, return type and example
---
## Method: messages.getDocumentByHash  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|sha256|[bytes](../types/bytes.md) | Yes|
|size|[int](../types/int.md) | Yes|
|mime\_type|[string](../types/string.md) | Yes|


### Return type: [Document](../types/Document.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|SHA256_HASH_INVALID|The provided SHA256 hash is invalid|


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

$Document = $MadelineProto->messages->getDocumentByHash(['sha256' => 'bytes', 'size' => int, 'mime_type' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.getDocumentByHash
* params - `{"sha256": "bytes", "size": int, "mime_type": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getDocumentByHash`

Parameters:

sha256 - Json encoded bytes

size - Json encoded int

mime_type - Json encoded string




Or, if you're into Lua:

```
Document = messages.getDocumentByHash({sha256='bytes', size=int, mime_type='string', })
```

