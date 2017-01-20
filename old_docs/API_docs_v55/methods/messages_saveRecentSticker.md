---
title: messages.saveRecentSticker
description: messages.saveRecentSticker parameters, return type and example
---
## Method: messages.saveRecentSticker  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[InputDocument](../types/InputDocument.md) | Required|
|unsave|[Bool](../types/Bool.md) | Required|


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

$Bool = $MadelineProto->messages->saveRecentSticker(['id' => InputDocument, 'unsave' => Bool, ]);
```