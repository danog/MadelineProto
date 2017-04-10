---
title: channels.getFullChannel
description: channels.getFullChannel parameters, return type and example
---
## Method: channels.getFullChannel  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|channel|[InputChannel](../types/InputChannel.md) | Yes|


### Return type: [messages\_ChatFull](../types/messages_ChatFull.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $this->bot_login($token);
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

$messages_ChatFull = $MadelineProto->channels->getFullChannel(['channel' => InputChannel, ]);
```

Or, if you're into Lua:

```
messages_ChatFull = channels.getFullChannel({channel=InputChannel, })
```

