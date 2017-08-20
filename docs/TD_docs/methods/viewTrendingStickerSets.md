---
title: viewTrendingStickerSets
description: Trending sticker sets are viewed by the user
---
## Method: viewTrendingStickerSets  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Trending sticker sets are viewed by the user

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|sticker\_set\_ids|Array of [long](../types/long.md) | Yes|Identifiers of viewed trending sticker sets|


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

$Ok = $MadelineProto->viewTrendingStickerSets(['sticker_set_ids' => [long], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - viewTrendingStickerSets
* params - `{"sticker_set_ids": [long], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/viewTrendingStickerSets`

Parameters:

sticker_set_ids - Json encoded  array of long




Or, if you're into Lua:

```
Ok = viewTrendingStickerSets({sticker_set_ids={long}, })
```

