---
title: messages.getMessages
description: messages.getMessages parameters, return type and example
---
## Method: messages.getMessages  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|Array of [int](../types/int.md) | Yes|


### Return type: [messages\_Messages](../types/messages_Messages.md)

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

$messages_Messages = $MadelineProto->messages->getMessages(['id' => [int], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.getMessages
* params - `{"id": [int], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getMessages`

Parameters:

id - Json encoded  array of int




Or, if you're into Lua:

```
messages_Messages = messages.getMessages({id={int}, })
```

