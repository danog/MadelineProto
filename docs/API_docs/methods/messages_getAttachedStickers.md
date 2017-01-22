---
title: messages.getAttachedStickers
description: messages.getAttachedStickers parameters, return type and example
---
## Method: messages.getAttachedStickers  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|media|[InputStickeredMedia](../types/InputStickeredMedia.md) | Required|


### Return type: [Vector\_of\_StickerSetCovered](../types/StickerSetCovered.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Vector_of_StickerSetCovered = $MadelineProto->messages->getAttachedStickers(['media' => InputStickeredMedia, ]);
```