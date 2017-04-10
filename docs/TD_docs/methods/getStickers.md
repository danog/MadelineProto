---
title: getStickers
description: Returns stickers corresponding to given emoji
---
## Method: getStickers  
[Back to methods index](index.md)


Returns stickers corresponding to given emoji

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|emoji|[string](../types/string.md) | Yes|String representation of emoji. If empty, returns all known stickers|


### Return type: [Stickers](../types/Stickers.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $this->bot_login($token);
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

$Stickers = $MadelineProto->getStickers(['emoji' => string, ]);
```

Or, if you're into Lua:

```
Stickers = getStickers({emoji=string, })
```

