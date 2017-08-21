---
title: messages.reorderPinnedDialogs
description: messages.reorderPinnedDialogs parameters, return type and example
---
## Method: messages.reorderPinnedDialogs  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|force|[Bool](../types/Bool.md) | Optional|
|order|Array of [InputPeer](../types/InputPeer.md) | Yes|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->messages->reorderPinnedDialogs(['force' => Bool, 'order' => [InputPeer], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.reorderPinnedDialogs
* params - `{"force": Bool, "order": [InputPeer], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.reorderPinnedDialogs`

Parameters:

force - Json encoded Bool

order - Json encoded  array of InputPeer




Or, if you're into Lua:

```
Bool = messages.reorderPinnedDialogs({force=Bool, order={InputPeer}, })
```

