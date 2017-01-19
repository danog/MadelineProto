---
title: messages.saveDraft
description: messages.saveDraft parameters, return type and example
---
## Method: messages.saveDraft  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|no\_webpage|[Bool](../types/Bool.md) | Optional|
|reply\_to\_msg\_id|[int](../types/int.md) | Optional|
|peer|[InputPeer](../types/InputPeer.md) | Required|
|message|[string](../types/string.md) | Required|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->messages->saveDraft(['no_webpage' => Bool, 'reply_to_msg_id' => int, 'peer' => InputPeer, 'message' => string, 'entities' => [MessageEntity], ]);
```