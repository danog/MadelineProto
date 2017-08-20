---
title: deleteMessages
description: Deletes messages. UpdateDeleteMessages will not be sent for messages deleted through that function
---
## Method: deleteMessages  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Deletes messages. UpdateDeleteMessages will not be sent for messages deleted through that function

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|message\_ids|Array of [long](../types/long.md) | Yes|Identifiers of messages to delete|


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

$Ok = $MadelineProto->deleteMessages(['chat_id' => InputPeer, 'message_ids' => [long], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - deleteMessages
* params - `{"chat_id": InputPeer, "message_ids": [long], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/deleteMessages`

Parameters:

chat_id - Json encoded InputPeer

message_ids - Json encoded  array of long




Or, if you're into Lua:

```
Ok = deleteMessages({chat_id=InputPeer, message_ids={long}, })
```

