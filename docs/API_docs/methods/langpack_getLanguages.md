---
title: langpack.getLanguages
description: Get available languages
---
## Method: langpack.getLanguages  
[Back to methods index](index.md)


Get available languages



### Return type: [Vector\_of\_LangPackLanguage](../types/LangPackLanguage.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Vector_of_LangPackLanguage = $MadelineProto->langpack->getLanguages();
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/langpack.getLanguages`

Parameters:




Or, if you're into Lua:

```
Vector_of_LangPackLanguage = langpack.getLanguages({})
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|LANG_PACK_INVALID|The provided language pack is invalid|


