---
title: messages.startBot
description: messages.startBot parameters, return type and example
---
## Method: messages.startBot  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|bot|[InputUser](../types/InputUser.md) | Required|
|peer|[InputPeer](../types/InputPeer.md) | Required|
|start\_param|[string](../types/string.md) | Required|


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

$Updates = $MadelineProto->messages->startBot(['bot' => InputUser, 'peer' => InputPeer, 'start_param' => string, ]);
```
