---
title: messages.deleteChatUser
description: messages.deleteChatUser parameters, return type and example
---
## Method: messages.deleteChatUser  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|chat\_id|[int](../types/int.md) | Yes|
|user\_id|[InputUser](../types/InputUser.md) | Yes|


### Return type: [messages\_StatedMessage](../types/messages_StatedMessage.md)

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

$messages_StatedMessage = $MadelineProto->messages->deleteChatUser(['chat_id' => int, 'user_id' => InputUser, ]);
```

Or, if you're into Lua:

```
messages_StatedMessage = messages.deleteChatUser({chat_id=int, user_id=InputUser, })
```

