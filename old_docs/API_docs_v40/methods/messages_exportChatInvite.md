---
title: messages.exportChatInvite
description: messages.exportChatInvite parameters, return type and example
---
## Method: messages.exportChatInvite  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|chat\_id|[InputChat](../types/InputChat.md) | Required|


### Return type: [ExportedChatInvite](../types/ExportedChatInvite.md)

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

$ExportedChatInvite = $MadelineProto->messages->exportChatInvite(['chat_id' => InputChat, ]);
```