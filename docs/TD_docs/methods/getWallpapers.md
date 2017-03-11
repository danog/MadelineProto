---
title: getWallpapers
description: Returns background wallpapers
---
## Method: getWallpapers  
[Back to methods index](index.md)


Returns background wallpapers

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|


### Return type: [Wallpapers](../types/Wallpapers.md)

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

$Wallpapers = $MadelineProto->getWallpapers();
```

Or, if you're into Lua:

```
Wallpapers = getWallpapers({})
```

