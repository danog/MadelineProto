---
title: messages.getAttachedStickers
description: messages.getAttachedStickers parameters, return type and example
---
## Method: messages.getAttachedStickers  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|media|[InputStickeredMedia](../types/InputStickeredMedia.md) | Yes|


### Return type: [Vector\_of\_StickerSetCovered](../types/StickerSetCovered.md)

### Can bots use this method: **NO**


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$Vector_of_StickerSetCovered = $MadelineProto->messages->getAttachedStickers(['media' => InputStickeredMedia, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getAttachedStickers`

Parameters:

media - Json encoded InputStickeredMedia




Or, if you're into Lua:

```
Vector_of_StickerSetCovered = messages.getAttachedStickers({media=InputStickeredMedia, })
```

