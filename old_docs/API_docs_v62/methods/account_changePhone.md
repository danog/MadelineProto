---
title: account.changePhone
description: account.changePhone parameters, return type and example
---
## Method: account.changePhone  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|phone\_number|[string](../types/string.md) | Yes|
|phone\_code\_hash|[string](../types/string.md) | Yes|
|phone\_code|[string](../types/string.md) | Yes|


### Return type: [User](../types/User.md)

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

$User = $MadelineProto->account->changePhone(['phone_number' => string, 'phone_code_hash' => string, 'phone_code' => string, ]);
```

Or, if you're into Lua:

```
User = account.changePhone({phone_number=string, phone_code_hash=string, phone_code=string, })
```

