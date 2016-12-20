---
title: messages_addChatUser
description: messages_addChatUser parameters, return type and example
---
## Method: messages\_addChatUser  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|chat\_id|[int](../types/int.md) | Required|
|user\_id|[InputUser](../types/InputUser.md) | Required|
|fwd\_limit|[int](../types/int.md) | Required|


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

$Updates = $MadelineProto->messages_addChatUser(['chat_id' => int, 'user_id' => InputUser, 'fwd_limit' => int, ]);
```