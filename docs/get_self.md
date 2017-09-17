---
title: get_self
description: get_self parameters, return type and example
---
## Method: get_self  


### Return type: [User object](API_docs/types/User.md)

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

$User = $MadelineProto->get_self();
```

Or, if you're into Lua:

```
User = get_self()
```

