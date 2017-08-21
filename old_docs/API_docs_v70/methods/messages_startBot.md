---
title: messages.startBot
description: messages.startBot parameters, return type and example
---
## Method: messages.startBot  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|bot|[InputUser](../types/InputUser.md) | Yes|
|peer|[InputPeer](../types/InputPeer.md) | Yes|
|start\_param|[string](../types/string.md) | Yes|


### Return type: [Updates](../types/Updates.md)

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

$Updates = $MadelineProto->messages->startBot(['bot' => InputUser, 'peer' => InputPeer, 'start_param' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.startBot
* params - `{"bot": InputUser, "peer": InputPeer, "start_param": "string", }`



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

