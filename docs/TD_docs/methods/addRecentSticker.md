---
title: addRecentSticker
description: Manually adds new sticker to the list of recently used stickers. New sticker is added to the beginning of the list. If the sticker is already in the list, at first it is removed from the list
---
## Method: addRecentSticker  
[Back to methods index](index.md)


Manually adds new sticker to the list of recently used stickers. New sticker is added to the beginning of the list. If the sticker is already in the list, at first it is removed from the list

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|is\_attached|[Bool](../types/Bool.md) | Yes|Pass true to add the sticker to the list of stickers recently attached to photo or video files, pass false to add the sticker to the list of recently sent stickers|
|sticker|[InputFile](../types/InputFile.md) | Yes|Sticker file to add|


### Return type: [Stickers](../types/Stickers.md)

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

$Stickers = $MadelineProto->addRecentSticker(['is_attached' => Bool, 'sticker' => InputFile, ]);
```

Or, if you're into Lua:

```
Stickers = addRecentSticker({is_attached=Bool, sticker=InputFile, })
```

