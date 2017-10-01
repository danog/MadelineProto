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
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
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

