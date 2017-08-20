---
title: answerCallbackQuery
description: Bots only. Sets result of the callback query
---
## Method: answerCallbackQuery  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Bots only. Sets result of the callback query

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|callback\_query\_id|[long](../types/long.md) | Yes|Identifier of the callback query|
|text|[string](../types/string.md) | Yes|Text of the answer|
|show\_alert|[Bool](../types/Bool.md) | Yes|If true, an alert should be shown to the user instead of a toast|
|url|[string](../types/string.md) | Yes|Url to be opened|
|cache\_time|[int](../types/int.md) | Yes|Allowed time to cache result of the query in seconds|


### Return type: [Ok](../types/Ok.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Ok = $MadelineProto->answerCallbackQuery(['callback_query_id' => long, 'text' => 'string', 'show_alert' => Bool, 'url' => 'string', 'cache_time' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - answerCallbackQuery
* params - `{"callback_query_id": long, "text": "string", "show_alert": Bool, "url": "string", "cache_time": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/answerCallbackQuery`

Parameters:

callback_query_id - Json encoded long

text - Json encoded string

show_alert - Json encoded Bool

url - Json encoded string

cache_time - Json encoded int




Or, if you're into Lua:

```
Ok = answerCallbackQuery({callback_query_id=long, text='string', show_alert=Bool, url='string', cache_time=int, })
```

