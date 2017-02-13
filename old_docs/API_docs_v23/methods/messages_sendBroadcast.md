---
title: messages.sendBroadcast
description: messages.sendBroadcast parameters, return type and example
---
## Method: messages.sendBroadcast  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|contacts|Array of [InputUser](../types/InputUser.md) | Required|
|message|[string](../types/string.md) | Required|
|media|[InputMedia](../types/InputMedia.md) | Required|


### Return type: [messages\_StatedMessages](../types/messages_StatedMessages.md)

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

$messages_StatedMessages = $MadelineProto->messages->sendBroadcast(['contacts' => [InputUser], 'message' => string, 'media' => InputMedia, ]);
```
