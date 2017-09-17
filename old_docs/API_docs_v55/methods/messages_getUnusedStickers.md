---
title: messages.getUnusedStickers
description: messages.getUnusedStickers parameters, return type and example
---
## Method: messages.getUnusedStickers  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|limit|[int](../types/int.md) | Yes|


### Return type: [Vector\_of\_StickerSetCovered](../types/StickerSetCovered.md)

### Can bots use this method: **YES**


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

$Vector_of_StickerSetCovered = $MadelineProto->messages->getUnusedStickers(['limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.getUnusedStickers
* params - `{"limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getUnusedStickers`

Parameters:

limit - Json encoded int




Or, if you're into Lua:

```
Vector_of_StickerSetCovered = messages.getUnusedStickers({limit=int, })
```

