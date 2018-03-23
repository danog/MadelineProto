---
title: langpack.getStrings
description: Get language pack strings
---
## Method: langpack.getStrings  
[Back to methods index](index.md)


Get language pack strings

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|lang\_code|[CLICK ME string](../types/string.md) | Yes|Language code|
|keys|Array of [CLICK ME string](../types/string.md) | Yes|Keys|


### Return type: [Vector\_of\_LangPackString](../types/LangPackString.md)

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

$Vector_of_LangPackString = $MadelineProto->langpack->getStrings(['lang_code' => 'string', 'keys' => ['string', 'string'], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/langpack.getStrings`

Parameters:

lang_code - Json encoded string

keys - Json encoded  array of string




Or, if you're into Lua:

```
Vector_of_LangPackString = langpack.getStrings({lang_code='string', keys={'string'}, })
```

