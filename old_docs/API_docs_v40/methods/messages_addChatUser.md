---
title: messages.addChatUser
description: messages.addChatUser parameters, return type and example
---
## Method: messages.addChatUser  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|chat\_id|[InputChat](../types/InputChat.md) | Yes|
|user\_id|[InputUser](../types/InputUser.md) | Yes|
|fwd\_limit|[int](../types/int.md) | Yes|


### Return type: [Updates](../types/Updates.md)

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

$Updates = $MadelineProto->messages->addChatUser(['chat_id' => InputChat, 'user_id' => InputUser, 'fwd_limit' => int, ]);
```

Or, if you're into Lua:

```
Updates = messages.addChatUser({chat_id=InputChat, user_id=InputUser, fwd_limit=int, })
```

