---
title: langpack.getDifference
description: langpack.getDifference parameters, return type and example
---
## Method: langpack.getDifference  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|from\_version|[int](../types/int.md) | Yes|


### Return type: [LangPackDifference](../types/LangPackDifference.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|LANG_PACK_INVALID|The provided language pack is invalid|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$LangPackDifference = $MadelineProto->langpack->getDifference(['from_version' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/langpack.getDifference`

Parameters:

from_version - Json encoded int




Or, if you're into Lua:

```
LangPackDifference = langpack.getDifference({from_version=int, })
```

