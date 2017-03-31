---
title: auth.resetAuthorizations
description: auth.resetAuthorizations parameters, return type and example
---
## Method: auth.resetAuthorizations  
[Back to methods index](index.md)




### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->auth->resetAuthorizations();
```

Or, if you're into Lua:

```
Bool = auth.resetAuthorizations({})
```

