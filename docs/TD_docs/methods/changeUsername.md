---
title: changeUsername
description: Changes username of logged in user. If something changes, updateUser will be sent
---
## Method: changeUsername  
[Back to methods index](index.md)


Changes username of logged in user. If something changes, updateUser will be sent

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|username|[string](../types/string.md) | Yes|New value of username. Use empty string to remove username|


### Return type: [Ok](../types/Ok.md)

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

$Ok = $MadelineProto->changeUsername(['username' => string, ]);
```

Or, if you're into Lua:

```
Ok = changeUsername({username=string, })
```

