---
title: messages.createChannel
description: messages.createChannel parameters, return type and example
---
## Method: messages.createChannel  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|title|[string](../types/string.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


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

$Updates = $MadelineProto->messages->createChannel(['title' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.createChannel
* params - `{"title": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.createChannel`

Parameters:

title - Json encoded string




Or, if you're into Lua:

```
Updates = messages.createChannel({title='string', })
```

