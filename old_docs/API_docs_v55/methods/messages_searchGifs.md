---
title: messages.searchGifs
description: messages.searchGifs parameters, return type and example
---
## Method: messages.searchGifs  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|q|[string](../types/string.md) | Required|
|offset|[int](../types/int.md) | Required|


### Return type: [messages\_FoundGifs](../types/messages_FoundGifs.md)

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

$messages_FoundGifs = $MadelineProto->messages->searchGifs(['q' => string, 'offset' => int, ]);
```
