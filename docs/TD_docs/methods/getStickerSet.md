---
title: getStickerSet
description: Returns information about sticker set by its identifier
---
## Method: getStickerSet  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns information about sticker set by its identifier

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|set\_id|[long](../types/long.md) | Yes|Identifier of the sticker set|


### Return type: [StickerSet](../types/StickerSet.md)

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

$StickerSet = $MadelineProto->getStickerSet(['set_id' => long, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - getStickerSet
* params - `{"set_id": long, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/getStickerSet`

Parameters:

set_id - Json encoded long




Or, if you're into Lua:

```
StickerSet = getStickerSet({set_id=long, })
```

