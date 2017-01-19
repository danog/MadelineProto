---
title: messages.getAllStickers
description: messages.getAllStickers parameters, return type and example
---
## Method: messages.getAllStickers  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|hash|[string](../types/string.md) | Required|


### Return type: [messages\_AllStickers](../types/messages_AllStickers.md)

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

$messages_AllStickers = $MadelineProto->messages->getAllStickers(['hash' => string, ]);
```