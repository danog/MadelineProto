---
title: messages.getWebPage
description: messages.getWebPage parameters, return type and example
---
## Method: messages.getWebPage  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|url|[string](../types/string.md) | Yes|
|hash|[int](../types/int.md) | Yes|


### Return type: [WebPage](../types/WebPage.md)

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

$WebPage = $MadelineProto->messages->getWebPage(['url' => string, 'hash' => int, ]);
```

Or, if you're into Lua:

```
WebPage = messages.getWebPage({url=string, hash=int, })
```

