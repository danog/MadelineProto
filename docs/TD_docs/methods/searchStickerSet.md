---
title: searchStickerSet
description: Searches sticker set by its short name
---
## Method: searchStickerSet  
[Back to methods index](index.md)


Searches sticker set by its short name

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|name|[string](../types/string.md) | Yes|Name of the sticker set|


### Return type: [StickerSet](../types/StickerSet.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$StickerSet = $MadelineProto->searchStickerSet(['name' => string, ]);
```

Or, if you're into Lua:

```
StickerSet = searchStickerSet({name=string, })
```

