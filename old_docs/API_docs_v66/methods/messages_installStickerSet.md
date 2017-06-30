---
title: messages.installStickerSet
description: messages.installStickerSet parameters, return type and example
---
## Method: messages.installStickerSet  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|stickerset|[InputStickerSet](../types/InputStickerSet.md) | Yes|
|archived|[Bool](../types/Bool.md) | Yes|


### Return type: [messages\_StickerSetInstallResult](../types/messages_StickerSetInstallResult.md)

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

$messages_StickerSetInstallResult = $MadelineProto->messages->installStickerSet(['stickerset' => InputStickerSet, 'archived' => Bool, ]);
```

Or, if you're into Lua:

```
messages_StickerSetInstallResult = messages.installStickerSet({stickerset=InputStickerSet, archived=Bool, })
```

