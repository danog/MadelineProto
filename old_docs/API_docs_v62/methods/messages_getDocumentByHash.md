---
title: messages.getDocumentByHash
description: Get document by SHA256 hash
---
## Method: messages.getDocumentByHash  
[Back to methods index](index.md)


Get document by SHA256 hash

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|sha256|[bytes](../types/bytes.md) | Yes|`hash('sha256', $filename, true);`|
|size|[int](../types/int.md) | Yes|The file size|
|mime\_type|[string](../types/string.md) | Yes|The mime type of the file|


### Return type: [Document](../types/Document.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Document = $MadelineProto->messages->getDocumentByHash(['sha256' => 'bytes', 'size' => int, 'mime_type' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

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

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|SHA256_HASH_INVALID|The provided SHA256 hash is invalid|


