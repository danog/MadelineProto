---
title: stickers.addStickerToSet
description: stickers.addStickerToSet parameters, return type and example
---
## Method: stickers.addStickerToSet  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|stickerset|[InputStickerSet](../types/InputStickerSet.md) | Yes|
|sticker|[InputStickerSetItem](../types/InputStickerSetItem.md) | Yes|


### Return type: [messages\_StickerSet](../types/messages_StickerSet.md)

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

$messages_StickerSet = $MadelineProto->stickers->addStickerToSet(['stickerset' => InputStickerSet, 'sticker' => InputStickerSetItem, ]);
```

Or, if you're into Lua:

```
messages_StickerSet = stickers.addStickerToSet({stickerset=InputStickerSet, sticker=InputStickerSetItem, })
```

