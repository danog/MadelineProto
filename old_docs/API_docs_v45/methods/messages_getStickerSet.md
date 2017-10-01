---
title: messages.getStickerSet
description: messages.getStickerSet parameters, return type and example
---
## Method: messages.getStickerSet  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|stickerset|[InputStickerSet](../types/InputStickerSet.md) | Yes|


### Return type: [messages\_StickerSet](../types/messages_StickerSet.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|STICKERSET_INVALID|The provided sticker set is invalid|


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

$messages_StickerSet = $MadelineProto->messages->getStickerSet(['stickerset' => InputStickerSet, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.getStickerSet
* params - `{"stickerset": InputStickerSet, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getStickerSet`

Parameters:

stickerset - Json encoded InputStickerSet




Or, if you're into Lua:

```
messages_StickerSet = messages.getStickerSet({stickerset=InputStickerSet, })
```

