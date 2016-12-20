---
title: messages_getRecentStickers
---
## Method: messages\_getRecentStickers  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|attached|[Bool](../types/Bool.md) | Optional|
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

$messages_RecentStickers = $MadelineProto->messages_getRecentStickers(['attached' => Bool, 'hash' => int, ]);
```