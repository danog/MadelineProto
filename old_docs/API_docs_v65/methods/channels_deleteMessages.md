---
title: channels.deleteMessages
description: channels.deleteMessages parameters, return type and example
---
## Method: channels.deleteMessages  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[InputChannel](../types/InputChannel.md) | Yes|
|id|Array of [int](../types/int.md) | Yes|


### Return type: [messages\_AffectedMessages](../types/messages_AffectedMessages.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|MESSAGE_DELETE_FORBIDDEN|You can't delete one of the messages you tried to delete, most likely because it is a service message.|


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

$messages_AffectedMessages = $MadelineProto->channels->deleteMessages(['channel' => InputChannel, 'id' => [int], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.deleteMessages
* params - `{"channel": InputChannel, "id": [int], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.deleteMessages`

Parameters:

channel - Json encoded InputChannel

id - Json encoded  array of int




Or, if you're into Lua:

```
messages_AffectedMessages = channels.deleteMessages({channel=InputChannel, id={int}, })
```

