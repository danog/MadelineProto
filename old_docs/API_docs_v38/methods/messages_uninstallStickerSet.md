---
title: messages.uninstallStickerSet
description: messages.uninstallStickerSet parameters, return type and example
---
## Method: messages.uninstallStickerSet  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|stickerset|[InputStickerSet](../types/InputStickerSet.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|STICKERSET_INVALID|The provided sticker set is invalid|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Bool = $MadelineProto->messages->uninstallStickerSet(['stickerset' => InputStickerSet, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.uninstallStickerSet`

Parameters:

stickerset - Json encoded InputStickerSet




Or, if you're into Lua:

```
Bool = messages.uninstallStickerSet({stickerset=InputStickerSet, })
```

