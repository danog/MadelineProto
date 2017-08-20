---
title: getMessages
description: Returns information about messages. If message is not found, returns null on the corresponding position of the result
---
## Method: getMessages  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns information about messages. If message is not found, returns null on the corresponding position of the result

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Identifier of the chat, messages belongs to|
|message\_ids|Array of [long](../types/long.md) | Yes|Identifiers of the messages to get|


### Return type: [Messages](../types/Messages.md)

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

$Messages = $MadelineProto->getMessages(['chat_id' => InputPeer, 'message_ids' => [long], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - getMessages
* params - `{"chat_id": InputPeer, "message_ids": [long], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/getMessages`

Parameters:

chat_id - Json encoded InputPeer

message_ids - Json encoded  array of long




Or, if you're into Lua:

```
Messages = getMessages({chat_id=InputPeer, message_ids={long}, })
```

