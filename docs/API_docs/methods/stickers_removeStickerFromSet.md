---
title: stickers.removeStickerFromSet
description: stickers.removeStickerFromSet parameters, return type and example
---
## Method: stickers.removeStickerFromSet  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|sticker|[InputDocument](../types/InputDocument.md) | Yes|


### Return type: [messages\_StickerSet](../types/messages_StickerSet.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|BOT_MISSING|This method can only be run by a bot|
|STICKER_INVALID|The provided sticker is invalid|


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

$messages_StickerSet = $MadelineProto->stickers->removeStickerFromSet(['sticker' => InputDocument, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - stickers.removeStickerFromSet
* params - `{"sticker": InputDocument, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/stickers.removeStickerFromSet`

Parameters:

sticker - Json encoded InputDocument




Or, if you're into Lua:

```
messages_StickerSet = stickers.removeStickerFromSet({sticker=InputDocument, })
```

