---
title: messages_sendMedia
description: messages_sendMedia parameters, return type and example
---
## Method: messages\_sendMedia  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|silent|[Bool](../types/Bool.md) | Optional|
|background|[Bool](../types/Bool.md) | Optional|
|clear\_draft|[Bool](../types/Bool.md) | Optional|
|peer|[InputPeer](../types/InputPeer.md) | Required|
|reply\_to\_msg\_id|[int](../types/int.md) | Optional|
|media|[InputMedia](../types/InputMedia.md) | Required|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|


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

$Updates = $MadelineProto->messages->sendMedia(['silent' => Bool, 'background' => Bool, 'clear_draft' => Bool, 'peer' => InputPeer, 'reply_to_msg_id' => int, 'media' => InputMedia, 'reply_markup' => ReplyMarkup, ]);
```