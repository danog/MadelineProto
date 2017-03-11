---
title: getSavedAnimations
description: Returns saved animations
---
## Method: getSavedAnimations  
[Back to methods index](index.md)


Returns saved animations

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|


### Return type: [Animations](../types/Animations.md)

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

$Animations = $MadelineProto->getSavedAnimations();
```

Or, if you're into Lua:

```
Animations = getSavedAnimations({})
```

