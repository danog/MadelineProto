---
title: getCreatedPublicChannels
description: Returns list of created public channels
---
## Method: getCreatedPublicChannels  
[Back to methods index](index.md)


Returns list of created public channels

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|


### Return type: [Channels](../types/Channels.md)

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

$Channels = $MadelineProto->getCreatedPublicChannels();
```

Or, if you're into Lua:

```
Channels = getCreatedPublicChannels({})
```

