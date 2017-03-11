---
title: getStickerSets
description: Returns list of installed sticker sets without archived sticker sets
---
## Method: getStickerSets  
[Back to methods index](index.md)


Returns list of installed sticker sets without archived sticker sets

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|is\_masks|[Bool](../types/Bool.md) | Yes|Pass true to return masks, pass false to return stickers|


### Return type: [StickerSets](../types/StickerSets.md)

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

$StickerSets = $MadelineProto->getStickerSets(['is_masks' => Bool, ]);
```

Or, if you're into Lua:

```
StickerSets = getStickerSets({is_masks=Bool, })
```

