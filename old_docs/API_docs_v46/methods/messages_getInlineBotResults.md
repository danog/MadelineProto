---
title: messages.getInlineBotResults
description: messages.getInlineBotResults parameters, return type and example
---
## Method: messages.getInlineBotResults  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|bot|[InputUser](../types/InputUser.md) | Yes|
|query|[string](../types/string.md) | Yes|
|offset|[string](../types/string.md) | Yes|


### Return type: [messages\_BotResults](../types/messages_BotResults.md)

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

$messages_BotResults = $MadelineProto->messages->getInlineBotResults(['bot' => InputUser, 'query' => 'string', 'offset' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.getInlineBotResults
* params - `{"bot": InputUser, "query": "string", "offset": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getInlineBotResults`

Parameters:

bot - Json encoded InputUser

query - Json encoded string

offset - Json encoded string




Or, if you're into Lua:

```
messages_BotResults = messages.getInlineBotResults({bot=InputUser, query='string', offset='string', })
```

