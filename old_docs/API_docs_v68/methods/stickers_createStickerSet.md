---
title: stickers.createStickerSet
description: stickers.createStickerSet parameters, return type and example
---
## Method: stickers.createStickerSet  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|masks|[Bool](../types/Bool.md) | Optional|
|user\_id|[InputUser](../types/InputUser.md) | Yes|
|title|[string](../types/string.md) | Yes|
|short\_name|[string](../types/string.md) | Yes|
|stickers|Array of [InputStickerSetItem](../types/InputStickerSetItem.md) | Yes|


### Return type: [messages\_StickerSet](../types/messages_StickerSet.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|BOT_MISSING|This method can only be run by a bot|
|USER_ID_INVALID|The provided user ID is invalid|


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

$messages_StickerSet = $MadelineProto->stickers->createStickerSet(['masks' => Bool, 'user_id' => InputUser, 'title' => 'string', 'short_name' => 'string', 'stickers' => [InputStickerSetItem], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - stickers.createStickerSet
* params - `{"masks": Bool, "user_id": InputUser, "title": "string", "short_name": "string", "stickers": [InputStickerSetItem], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/stickers.createStickerSet`

Parameters:

masks - Json encoded Bool

user_id - Json encoded InputUser

title - Json encoded string

short_name - Json encoded string

stickers - Json encoded  array of InputStickerSetItem




Or, if you're into Lua:

```
messages_StickerSet = stickers.createStickerSet({masks=Bool, user_id=InputUser, title='string', short_name='string', stickers={InputStickerSetItem}, })
```

