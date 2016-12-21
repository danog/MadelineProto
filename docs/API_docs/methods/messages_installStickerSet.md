---
title: messages_installStickerSet
description: messages_installStickerSet parameters, return type and example
---
## Method: messages\_installStickerSet  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|stickerset|[InputStickerSet](../types/InputStickerSet.md) | Required|
|archived|[Bool](../types/Bool.md) | Required|


### Return type: [messages\_StickerSetInstallResult](../types/messages_StickerSetInstallResult.md)

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

$messages_StickerSetInstallResult = $MadelineProto->messages->installStickerSet(['stickerset' => InputStickerSet, 'archived' => Bool, ]);
```