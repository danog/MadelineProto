---
title: messages_getFeaturedStickers
description: messages_getFeaturedStickers parameters, return type and example
---
## Method: messages\_getFeaturedStickers  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|hash|[int](../types/int.md) | Required|


### Return type: [messages\_FeaturedStickers](../types/messages_FeaturedStickers.md)

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

$messages_FeaturedStickers = $MadelineProto->messages->getFeaturedStickers(['hash' => int, ]);
```