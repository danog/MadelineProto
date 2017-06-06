---
title: terminateAllOtherSessions
description: Terminates all other sessions of logged in user
---
## Method: terminateAllOtherSessions  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Terminates all other sessions of logged in user

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|


### Return type: [Ok](../types/Ok.md)

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

$Ok = $MadelineProto->terminateAllOtherSessions();
```

Or, if you're into Lua:

```
Ok = terminateAllOtherSessions({})
```

