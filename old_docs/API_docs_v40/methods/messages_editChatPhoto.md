---
title: messages.editChatPhoto
description: messages.editChatPhoto parameters, return type and example
---
## Method: messages.editChatPhoto  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|chat\_id|[InputChat](../types/InputChat.md) | Yes|
|photo|[InputChatPhoto](../types/InputChatPhoto.md) | Yes|


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

$Updates = $MadelineProto->messages->editChatPhoto(['chat_id' => InputChat, 'photo' => InputChatPhoto, ]);
```

Or, if you're into Lua:

```
Updates = messages.editChatPhoto({chat_id=InputChat, photo=InputChatPhoto, })
```

