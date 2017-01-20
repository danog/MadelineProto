---
title: messages.sendInlineBotResult
description: messages.sendInlineBotResult parameters, return type and example
---
## Method: messages.sendInlineBotResult  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|broadcast|[Bool](../types/Bool.md) | Optional|
|peer|[InputPeer](../types/InputPeer.md) | Required|
|reply\_to\_msg\_id|[int](../types/int.md) | Optional|
|query\_id|[long](../types/long.md) | Required|
|id|[string](../types/string.md) | Required|


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

$Updates = $MadelineProto->messages->sendInlineBotResult(['broadcast' => Bool, 'peer' => InputPeer, 'reply_to_msg_id' => int, 'query_id' => long, 'id' => string, ]);
```