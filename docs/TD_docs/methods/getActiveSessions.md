---
title: getActiveSessions
description: Returns all active sessions of logged in user
---
## Method: getActiveSessions  
[Back to methods index](index.md)


Returns all active sessions of logged in user

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|


### Return type: [Sessions](../types/Sessions.md)

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

$Sessions = $MadelineProto->getActiveSessions();
```

Or, if you're into Lua:

```
Sessions = getActiveSessions({})
```

