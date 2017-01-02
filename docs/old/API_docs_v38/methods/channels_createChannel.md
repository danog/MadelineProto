---
title: channels_createChannel
description: channels_createChannel parameters, return type and example
---
## Method: channels\_createChannel  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|title|[string](../types/string.md) | Required|
|about|[string](../types/string.md) | Required|
|users|Array of [InputUser](../types/InputUser.md) | Required|


### Return type: [Updates](../types/Updates.md)

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

$Updates = $MadelineProto->channels->createChannel(['title' => string, 'about' => string, 'users' => [InputUser], ]);
```