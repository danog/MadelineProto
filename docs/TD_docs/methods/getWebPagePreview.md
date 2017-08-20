---
title: getWebPagePreview
description: Get web page preview by text of the message. Do not call this function to often
---
## Method: getWebPagePreview  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Get web page preview by text of the message. Do not call this function to often

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|message\_text|[string](../types/string.md) | Yes|Message text|


### Return type: [WebPage](../types/WebPage.md)

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

$WebPage = $MadelineProto->getWebPagePreview(['message_text' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - getWebPagePreview
* params - `{"message_text": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/getWebPagePreview`

Parameters:

message_text - Json encoded string




Or, if you're into Lua:

```
WebPage = getWebPagePreview({message_text='string', })
```

