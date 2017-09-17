---
title: channels.setStickers
description: channels.setStickers parameters, return type and example
---
## Method: channels.setStickers  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[InputChannel](../types/InputChannel.md) | Yes|
|stickerset|[InputStickerSet](../types/InputStickerSet.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|


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

$Bool = $MadelineProto->channels->setStickers(['channel' => InputChannel, 'stickerset' => InputStickerSet, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.setStickers
* params - `{"channel": InputChannel, "stickerset": InputStickerSet, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.setStickers`

Parameters:

channel - Json encoded InputChannel

stickerset - Json encoded InputStickerSet




Or, if you're into Lua:

```
Bool = channels.setStickers({channel=InputChannel, stickerset=InputStickerSet, })
```

