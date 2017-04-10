---
title: auth.sendInvites
description: auth.sendInvites parameters, return type and example
---
## Method: auth.sendInvites  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|phone\_numbers|Array of [string](../types/string.md) | Yes|
|message|[string](../types/string.md) | Yes|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->auth->sendInvites(['phone_numbers' => [string], 'message' => string, ]);
```

Or, if you're into Lua:

```
Bool = auth.sendInvites({phone_numbers={string}, message=string, })
```

