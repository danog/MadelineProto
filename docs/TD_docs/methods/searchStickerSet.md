---
title: searchStickerSet
description: Searches sticker set by its short name
---
## Method: searchStickerSet  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Searches sticker set by its short name

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|name|[string](../types/string.md) | Yes|Name of the sticker set|


### Return type: [StickerSet](../types/StickerSet.md)

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

$StickerSet = $MadelineProto->searchStickerSet(['name' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - searchStickerSet
* params - `{"name": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/searchStickerSet`

Parameters:

name - Json encoded string




Or, if you're into Lua:

```
StickerSet = searchStickerSet({name='string', })
```

