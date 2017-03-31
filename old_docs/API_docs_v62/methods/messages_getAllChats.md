---
title: messages.getAllChats
description: messages.getAllChats parameters, return type and example
---
## Method: messages.getAllChats  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|except\_ids|Array of [int](../types/int.md) | Yes|


### Return type: [messages\_Chats](../types/messages_Chats.md)

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

$messages_Chats = $MadelineProto->messages->getAllChats(['except_ids' => [int], ]);
```

Or, if you're into Lua:

```
messages_Chats = messages.getAllChats({except_ids={int}, })
```

