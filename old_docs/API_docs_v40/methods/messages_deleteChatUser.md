---
title: messages.deleteChatUser
description: messages.deleteChatUser parameters, return type and example
---
## Method: messages.deleteChatUser  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|chat\_id|[InputChat](../types/InputChat.md) | Required|
|user\_id|[InputUser](../types/InputUser.md) | Required|


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

$Updates = $MadelineProto->messages->deleteChatUser(['chat_id' => InputChat, 'user_id' => InputUser, ]);
```
