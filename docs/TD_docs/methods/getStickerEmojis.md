---
title: getStickerEmojis
description: Returns emojis corresponding to a sticker
---
## Method: getStickerEmojis  
[Back to methods index](index.md)


Returns emojis corresponding to a sticker

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|sticker|[InputFile](../types/InputFile.md) | Yes|Sticker file identifier|


### Return type: [StickerEmojis](../types/StickerEmojis.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $this->bot_login($token);
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

$StickerEmojis = $MadelineProto->getStickerEmojis(['sticker' => InputFile, ]);
```

Or, if you're into Lua:

```
StickerEmojis = getStickerEmojis({sticker=InputFile, })
```

