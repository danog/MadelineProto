---
title: getTrendingStickerSets
description: Returns list of trending sticker sets
---
## Method: getTrendingStickerSets  
[Back to methods index](index.md)


Returns list of trending sticker sets

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|


### Return type: [StickerSets](../types/StickerSets.md)

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

$StickerSets = $MadelineProto->getTrendingStickerSets();
```

Or, if you're into Lua:

```
StickerSets = getTrendingStickerSets({})
```

