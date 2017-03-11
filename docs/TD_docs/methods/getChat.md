---
title: getChat
description: Returns information about a chat by its identifier, offline request if current user is not a bot
---
## Method: getChat  
[Back to methods index](index.md)


Returns information about a chat by its identifier, offline request if current user is not a bot

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|


### Return type: [Chat](../types/Chat.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Chat = $MadelineProto->getChat(['chat_id' => long, ]);
```

Or, if you're into Lua:

```
Chat = getChat({chat_id=long, })
```

