---
title: geochats.editChatTitle
description: geochats.editChatTitle parameters, return type and example
---
## Method: geochats.editChatTitle  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|peer|[InputGeoChat](../types/InputGeoChat.md) | Yes|
|title|[string](../types/string.md) | Yes|
|address|[string](../types/string.md) | Yes|


### Return type: [geochats\_StatedMessage](../types/geochats_StatedMessage.md)

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

$geochats_StatedMessage = $MadelineProto->geochats->editChatTitle(['peer' => InputGeoChat, 'title' => string, 'address' => string, ]);
```

Or, if you're into Lua:

```
geochats_StatedMessage = geochats.editChatTitle({peer=InputGeoChat, title=string, address=string, })
```

