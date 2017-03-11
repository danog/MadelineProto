---
title: changeName
description: Changes first and last names of logged in user. If something changes, updateUser will be sent
---
## Method: changeName  
[Back to methods index](index.md)


Changes first and last names of logged in user. If something changes, updateUser will be sent

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|first\_name|[string](../types/string.md) | Yes|New value of user first name, 1-255 characters|
|last\_name|[string](../types/string.md) | Yes|New value of optional user last name, 0-255 characters|


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

$Ok = $MadelineProto->changeName(['first_name' => string, 'last_name' => string, ]);
```

Or, if you're into Lua:

```
Ok = changeName({first_name=string, last_name=string, })
```

