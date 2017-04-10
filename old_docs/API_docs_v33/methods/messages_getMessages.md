---
title: messages.getMessages
description: messages.getMessages parameters, return type and example
---
## Method: messages.getMessages  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|Array of [int](../types/int.md) | Yes|


### Return type: [messages\_Messages](../types/messages_Messages.md)

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

$messages_Messages = $MadelineProto->messages->getMessages(['id' => [int], ]);
```

Or, if you're into Lua:

```
messages_Messages = messages.getMessages({id={int}, })
```

