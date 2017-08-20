---
title: reorderStickerSets
description: Changes the order of installed sticker sets
---
## Method: reorderStickerSets  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Changes the order of installed sticker sets

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|is\_masks|[Bool](../types/Bool.md) | Yes|Pass true to change masks order, pass false to change stickers order|
|sticker\_set\_ids|Array of [long](../types/long.md) | Yes|Identifiers of installed sticker sets in the new right order|


### Return type: [Ok](../types/Ok.md)

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

$Ok = $MadelineProto->reorderStickerSets(['is_masks' => Bool, 'sticker_set_ids' => [long], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - reorderStickerSets
* params - `{"is_masks": Bool, "sticker_set_ids": [long], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/reorderStickerSets`

Parameters:

is_masks - Json encoded Bool

sticker_set_ids - Json encoded  array of long




Or, if you're into Lua:

```
Ok = reorderStickerSets({is_masks=Bool, sticker_set_ids={long}, })
```

