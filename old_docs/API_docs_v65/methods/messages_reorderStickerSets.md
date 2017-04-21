---
title: messages.reorderStickerSets
description: messages.reorderStickerSets parameters, return type and example
---
## Method: messages.reorderStickerSets  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|masks|[Bool](../types/Bool.md) | Optional|
|order|Array of [long](../types/long.md) | Yes|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->messages->reorderStickerSets(['masks' => Bool, 'order' => [long], ]);
```

Or, if you're into Lua:

```
Bool = messages.reorderStickerSets({masks=Bool, order={long}, })
```

