---
title: messages.getWebPage
description: messages.getWebPage parameters, return type and example
---
## Method: messages.getWebPage  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|url|[string](../types/string.md) | Yes|
|hash|[int](../types/int.md) | Yes|


### Return type: [WebPage](../types/WebPage.md)

### Can bots use this method: **NO**


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$WebPage = $MadelineProto->messages->getWebPage(['url' => 'string', 'hash' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getWebPage`

Parameters:

url - Json encoded string

hash - Json encoded int




Or, if you're into Lua:

```
WebPage = messages.getWebPage({url='string', hash=int, })
```

