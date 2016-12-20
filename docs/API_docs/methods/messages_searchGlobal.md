---
title: messages_searchGlobal
---
## Method: messages\_searchGlobal  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|q|[string](../types/string.md) | Required|
|offset\_date|[int](../types/int.md) | Required|
|offset\_peer|[InputPeer](../types/InputPeer.md) | Required|
|offset\_id|[int](../types/int.md) | Required|
|limit|[int](../types/int.md) | Required|


### Return type: [messages\_Messages](../types/messages_Messages.md)

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

$messages_Messages = $MadelineProto->messages_searchGlobal(['q' => string, 'offset_date' => int, 'offset_peer' => InputPeer, 'offset_id' => int, 'limit' => int, ]);
```