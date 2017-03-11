---
title: messages.toggleChatAdmins
description: messages.toggleChatAdmins parameters, return type and example
---
## Method: messages.toggleChatAdmins  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|chat\_id|[int](../types/int.md) | Yes|
|enabled|[Bool](../types/Bool.md) | Yes|


### Return type: [Updates](../types/Updates.md)

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

$Updates = $MadelineProto->messages->toggleChatAdmins(['chat_id' => int, 'enabled' => Bool, ]);
```

Or, if you're into Lua:

```
Updates = messages.toggleChatAdmins({chat_id=int, enabled=Bool, })
```

