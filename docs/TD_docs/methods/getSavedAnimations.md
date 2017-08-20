---
title: getSavedAnimations
description: Returns saved animations
---
## Method: getSavedAnimations  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns saved animations

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|


### Return type: [Animations](../types/Animations.md)

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

$Animations = $MadelineProto->getSavedAnimations();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - getSavedAnimations
* params - `{}`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/getSavedAnimations`

Parameters:




Or, if you're into Lua:

```
Animations = getSavedAnimations({})
```

