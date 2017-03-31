---
title: messages.checkChatInvite
description: messages.checkChatInvite parameters, return type and example
---
## Method: messages.checkChatInvite  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|hash|[string](../types/string.md) | Yes|


### Return type: [ChatInvite](../types/ChatInvite.md)

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

$ChatInvite = $MadelineProto->messages->checkChatInvite(['hash' => string, ]);
```

Or, if you're into Lua:

```
ChatInvite = messages.checkChatInvite({hash=string, })
```

