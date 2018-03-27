---
title: langpack.getDifference
description: Get language pack updates
---
## Method: langpack.getDifference  
[Back to methods index](index.md)


Get language pack updates

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|from\_version|[int](../types/int.md) | Yes|Previous version|


### Return type: [LangPackDifference](../types/LangPackDifference.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$LangPackDifference = $MadelineProto->langpack->getDifference(['from_version' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/langpack.getDifference`

Parameters:

from_version - Json encoded int




Or, if you're into Lua:

```
LangPackDifference = langpack.getDifference({from_version=int, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|LANG_PACK_INVALID|The provided language pack is invalid|


