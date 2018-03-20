---
title: messages.getDocumentByHash
description: messages.getDocumentByHash parameters, return type and example
---
## Method: messages.getDocumentByHash  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|sha256|[CLICK ME bytes](../types/bytes.md) | Yes|
|size|[CLICK ME int](../types/int.md) | Yes|
|mime\_type|[CLICK ME string](../types/string.md) | Yes|


### Return type: [Document](../types/Document.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|SHA256_HASH_INVALID|The provided SHA256 hash is invalid|


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

// !!! This API id/API hash combination will not work !!!
// !!! You must get your own @ my.telegram.org !!!
$api_id = 0;
$api_hash = '';

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['app_info' => ['api_id' => $api_id, 'api_hash' => $api_hash]]);
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

