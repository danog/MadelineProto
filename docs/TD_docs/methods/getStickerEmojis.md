---
title: getStickerEmojis
description: Returns emojis corresponding to a sticker
---
## Method: getStickerEmojis  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns emojis corresponding to a sticker

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|sticker|[InputFile](../types/InputFile.md) | Yes|Sticker file identifier|


### Return type: [StickerEmojis](../types/StickerEmojis.md)

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

$StickerEmojis = $MadelineProto->getStickerEmojis(['sticker' => InputFile, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - getStickerEmojis
* params - `{"sticker": InputFile, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/getStickerEmojis`

Parameters:

sticker - Json encoded InputFile




Or, if you're into Lua:

```
StickerEmojis = getStickerEmojis({sticker=InputFile, })
```

