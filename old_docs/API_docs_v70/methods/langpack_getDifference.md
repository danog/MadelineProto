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
|from\_version|[CLICK ME int](../types/int.md) | Yes|Previous version|


### Return type: [LangPackDifference](../types/LangPackDifference.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|LANG_PACK_INVALID|The provided language pack is invalid|


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

