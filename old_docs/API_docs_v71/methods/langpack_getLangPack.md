---
title: langpack.getLangPack
description: Get language pack
---
## Method: langpack.getLangPack  
[Back to methods index](index.md)


Get language pack

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|lang\_code|[string](../types/string.md) | Yes|Language code|


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

$LangPackDifference = $MadelineProto->langpack->getLangPack(['lang_code' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/langpack.getLangPack`

Parameters:

lang_code - Json encoded string




Or, if you're into Lua:

```
LangPackDifference = langpack.getLangPack({lang_code='string', })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|LANG_PACK_INVALID|The provided language pack is invalid|


