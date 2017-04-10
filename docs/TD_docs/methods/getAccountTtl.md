---
title: getAccountTtl
description: Returns period of inactivity, after which the account of currently logged in user will be automatically deleted
---
## Method: getAccountTtl  
[Back to methods index](index.md)


Returns period of inactivity, after which the account of currently logged in user will be automatically deleted

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|


### Return type: [AccountTtl](../types/AccountTtl.md)

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

$AccountTtl = $MadelineProto->getAccountTtl();
```

Or, if you're into Lua:

```
AccountTtl = getAccountTtl({})
```

