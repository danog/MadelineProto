---
title: langpack.getLanguages
description: langpack.getLanguages parameters, return type and example
---
## Method: langpack.getLanguages  
[Back to methods index](index.md)




### Return type: [Vector\_of\_LangPackLanguage](../types/LangPackLanguage.md)

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

$Vector_of_LangPackLanguage = $MadelineProto->langpack->getLanguages();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/langpack.getLanguages`

Parameters:




Or, if you're into Lua:

```
Vector_of_LangPackLanguage = langpack.getLanguages({})
```

