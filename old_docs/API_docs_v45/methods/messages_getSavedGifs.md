---
title: messages.getSavedGifs
description: messages.getSavedGifs parameters, return type and example
---
## Method: messages.getSavedGifs  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|hash|[int](../types/int.md) | Required|


### Return type: [messages\_SavedGifs](../types/messages_SavedGifs.md)

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

$messages_SavedGifs = $MadelineProto->messages->getSavedGifs(['hash' => int, ]);
```
