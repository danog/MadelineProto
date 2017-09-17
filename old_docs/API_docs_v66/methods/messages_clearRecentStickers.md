---
title: messages.clearRecentStickers
description: messages.clearRecentStickers parameters, return type and example
---
## Method: messages.clearRecentStickers  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|attached|[Bool](../types/Bool.md) | Optional|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


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

$Bool = $MadelineProto->messages->clearRecentStickers(['attached' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.clearRecentStickers`

Parameters:

attached - Json encoded Bool




Or, if you're into Lua:

```
Bool = messages.clearRecentStickers({attached=Bool, })
```

