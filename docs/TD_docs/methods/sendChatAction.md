---
title: sendChatAction
description: Sends notification about user activity in a chat
---
## Method: sendChatAction  
[Back to methods index](index.md)


Sends notification about user activity in a chat

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|
|action|[SendMessageAction](../types/SendMessageAction.md) | Yes|Action description|


### Return type: [Ok](../types/Ok.md)

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

$Ok = $MadelineProto->sendChatAction(['chat_id' => long, 'action' => SendMessageAction, ]);
```

Or, if you're into Lua:

```
Ok = sendChatAction({chat_id=long, action=SendMessageAction, })
```

