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

$LangPackDifference = $MadelineProto->langpack->getDifference(['from_version' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - langpack.getDifference
* params - `{"from_version": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/langpack.getDifference`

Parameters:

from_version - Json encoded int




Or, if you're into Lua:

```
LangPackDifference = langpack.getDifference({from_version=int, })
```

