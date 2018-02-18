---
title: messages.getStickers
description: messages.getStickers parameters, return type and example
---
## Method: messages.getStickers  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|emoticon|[string](../types/string.md) | Yes|
|hash|[string](../types/string.md) | Yes|


### Return type: [messages\_Stickers](../types/messages_Stickers.md)

### Can bots use this method: **YES**


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$messages_Stickers = $MadelineProto->messages->getStickers(['emoticon' => 'string', 'hash' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.getStickers
* params - `{"emoticon": "string", "hash": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getStickers`

Parameters:

emoticon - Json encoded string

hash - Json encoded string




Or, if you're into Lua:

```
messages_Stickers = messages.getStickers({emoticon='string', hash='string', })
```

