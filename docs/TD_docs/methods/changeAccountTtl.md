---
title: changeAccountTtl
description: Changes period of inactivity, after which the account of currently logged in user will be automatically deleted
---
## Method: changeAccountTtl  
[Back to methods index](index.md)


Changes period of inactivity, after which the account of currently logged in user will be automatically deleted

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|ttl|[accountTtl](../types/accountTtl.md) | Yes|New account TTL|


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

$Ok = $MadelineProto->changeAccountTtl(['ttl' => accountTtl, ]);
```

Or, if you're into Lua:

```
Ok = changeAccountTtl({ttl=accountTtl, })
```

