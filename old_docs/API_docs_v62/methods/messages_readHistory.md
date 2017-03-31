---
title: messages.readHistory
description: messages.readHistory parameters, return type and example
---
## Method: messages.readHistory  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|peer|[InputPeer](../types/InputPeer.md) | Yes|
|max\_id|[int](../types/int.md) | Yes|


### Return type: [messages\_AffectedMessages](../types/messages_AffectedMessages.md)

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

$messages_AffectedMessages = $MadelineProto->messages->readHistory(['peer' => InputPeer, 'max_id' => int, ]);
```

Or, if you're into Lua:

```
messages_AffectedMessages = messages.readHistory({peer=InputPeer, max_id=int, })
```

