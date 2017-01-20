---
title: messages.sendMessage
description: messages.sendMessage parameters, return type and example
---
## Method: messages.sendMessage  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|peer|[InputPeer](../types/InputPeer.md) | Required|
|reply\_to\_msg\_id|[int](../types/int.md) | Optional|
|message|[string](../types/string.md) | Required|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|


### Return type: [messages\_SentMessage](../types/messages_SentMessage.md)

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

$messages_SentMessage = $MadelineProto->messages->sendMessage(['peer' => InputPeer, 'reply_to_msg_id' => int, 'message' => string, 'reply_markup' => ReplyMarkup, ]);
```