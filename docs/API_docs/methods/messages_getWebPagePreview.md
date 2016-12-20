---
title: messages_getWebPagePreview
---
## Method: messages\_getWebPagePreview  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|message|[string](../types/string.md) | Required|


### Return type: [MessageMedia](../types/MessageMedia.md)

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

$MessageMedia = $MadelineProto->messages_getWebPagePreview(['message' => string, ]);
```