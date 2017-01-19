---
title: geochats.sendMessage
description: geochats.sendMessage parameters, return type and example
---
## Method: geochats.sendMessage  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|peer|[InputGeoChat](../types/InputGeoChat.md) | Required|
|message|[string](../types/string.md) | Required|


### Return type: [geochats\_StatedMessage](../types/geochats_StatedMessage.md)

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

$geochats_StatedMessage = $MadelineProto->geochats->sendMessage(['peer' => InputGeoChat, 'message' => string, ]);
```