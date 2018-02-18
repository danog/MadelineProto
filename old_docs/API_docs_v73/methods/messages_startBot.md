---
title: messages.startBot
description: messages.startBot parameters, return type and example
---
## Method: messages.startBot  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|bot|[InputUser](../types/InputUser.md) | Optional|
|peer|[InputPeer](../types/InputPeer.md) | Optional|
|start\_param|[string](../types/string.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|BOT_INVALID|This is not a valid bot|
|PEER_ID_INVALID|The provided peer id is invalid|
|START_PARAM_EMPTY|The start parameter is empty|
|START_PARAM_INVALID|Start parameter invalid|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$Updates = $MadelineProto->messages->startBot(['bot' => InputUser, 'peer' => InputPeer, 'start_param' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.startBot`

Parameters:

bot - Json encoded InputUser

peer - Json encoded InputPeer

start_param - Json encoded string




Or, if you're into Lua:

```
Updates = messages.startBot({bot=InputUser, peer=InputPeer, start_param='string', })
```

