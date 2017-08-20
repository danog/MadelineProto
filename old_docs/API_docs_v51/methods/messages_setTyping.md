---
title: messages.setTyping
description: messages.setTyping parameters, return type and example
---
## Method: messages.setTyping  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputPeer](../types/InputPeer.md) | Yes|
|action|[SendMessageAction](../types/SendMessageAction.md) | Yes|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->messages->setTyping(['peer' => InputPeer, 'action' => SendMessageAction, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.setTyping
* params - `{"peer": InputPeer, "action": SendMessageAction, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.setTyping`

Parameters:

peer - Json encoded InputPeer

action - Json encoded SendMessageAction




Or, if you're into Lua:

```
Bool = messages.setTyping({peer=InputPeer, action=SendMessageAction, })
```

