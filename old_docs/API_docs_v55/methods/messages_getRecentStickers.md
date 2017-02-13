---
title: messages.getRecentStickers
description: messages.getRecentStickers parameters, return type and example
---
## Method: messages.getRecentStickers  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|hash|[int](../types/int.md) | Required|


### Return type: [messages\_RecentStickers](../types/messages_RecentStickers.md)

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

$messages_RecentStickers = $MadelineProto->messages->getRecentStickers(['hash' => int, ]);
```
