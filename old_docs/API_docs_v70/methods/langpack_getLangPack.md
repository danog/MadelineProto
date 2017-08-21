---
title: langpack.getLangPack
description: langpack.getLangPack parameters, return type and example
---
## Method: langpack.getLangPack  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|lang\_code|[string](../types/string.md) | Yes|


### Return type: [LangPackDifference](../types/LangPackDifference.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$LangPackDifference = $MadelineProto->langpack->getLangPack(['lang_code' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - langpack.getLangPack
* params - `{"lang_code": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/langpack.getLangPack`

Parameters:

lang_code - Json encoded string




Or, if you're into Lua:

```
LangPackDifference = langpack.getLangPack({lang_code='string', })
```

