---
title: messages.getStickerSet
description: messages.getStickerSet parameters, return type and example
---
## Method: messages.getStickerSet  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|stickerset|[InputStickerSet](../types/InputStickerSet.md) | Yes|


### Return type: [messages\_StickerSet](../types/messages_StickerSet.md)

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

$messages_StickerSet = $MadelineProto->messages->getStickerSet(['stickerset' => InputStickerSet, ]);
```

Or, if you're into Lua:

```
messages_StickerSet = messages.getStickerSet({stickerset=InputStickerSet, })
```

