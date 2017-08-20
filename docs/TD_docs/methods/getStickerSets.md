---
title: getStickerSets
description: Returns list of installed sticker sets without archived sticker sets
---
## Method: getStickerSets  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns list of installed sticker sets without archived sticker sets

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|is\_masks|[Bool](../types/Bool.md) | Yes|Pass true to return masks, pass false to return stickers|


### Return type: [StickerSets](../types/StickerSets.md)

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

$StickerSets = $MadelineProto->getStickerSets(['is_masks' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - getStickerSets
* params - `{"is_masks": Bool, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/getStickerSets`

Parameters:

is_masks - Json encoded Bool




Or, if you're into Lua:

```
StickerSets = getStickerSets({is_masks=Bool, })
```

