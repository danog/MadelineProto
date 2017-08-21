---
title: langpack.getStrings
description: langpack.getStrings parameters, return type and example
---
## Method: langpack.getStrings  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|lang\_code|[string](../types/string.md) | Yes|
|keys|Array of [string](../types/string.md) | Yes|


### Return type: [Vector\_of\_LangPackString](../types/LangPackString.md)

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

$Vector_of_LangPackString = $MadelineProto->langpack->getStrings(['lang_code' => 'string', 'keys' => ['string'], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - langpack.getStrings
* params - `{"lang_code": "string", "keys": ["string"], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/langpack.getStrings`

Parameters:

lang_code - Json encoded string

keys - Json encoded  array of string




Or, if you're into Lua:

```
Vector_of_LangPackString = langpack.getStrings({lang_code='string', keys={'string'}, })
```

