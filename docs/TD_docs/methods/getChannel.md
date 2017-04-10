---
title: getChannel
description: Returns information about a channel by its identifier, offline request if current user is not a bot
---
## Method: getChannel  
[Back to methods index](index.md)


Returns information about a channel by its identifier, offline request if current user is not a bot

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|channel\_id|[int](../types/int.md) | Yes|Channel identifier|


### Return type: [Channel](../types/Channel.md)

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

$Channel = $MadelineProto->getChannel(['channel_id' => int, ]);
```

Or, if you're into Lua:

```
Channel = getChannel({channel_id=int, })
```

