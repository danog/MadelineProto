---
title: messages.receivedMessages
description: messages.receivedMessages parameters, return type and example
---
## Method: messages.receivedMessages  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|max\_id|[int](../types/int.md) | Required|


### Return type: [Vector\_of\_ReceivedNotifyMessage](../types/ReceivedNotifyMessage.md)

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

$Vector_of_ReceivedNotifyMessage = $MadelineProto->messages->receivedMessages(['max_id' => int, ]);
```
