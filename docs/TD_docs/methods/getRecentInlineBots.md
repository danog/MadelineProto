---
title: getRecentInlineBots
description: Returns up to 20 recently used inline bots in the order of the last usage
---
## Method: getRecentInlineBots  
[Back to methods index](index.md)


Returns up to 20 recently used inline bots in the order of the last usage

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|


### Return type: [Users](../types/Users.md)

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

$Users = $MadelineProto->getRecentInlineBots();
```

Or, if you're into Lua:

```
Users = getRecentInlineBots({})
```

