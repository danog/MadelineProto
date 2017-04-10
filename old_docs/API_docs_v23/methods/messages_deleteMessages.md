---
title: messages.deleteMessages
description: messages.deleteMessages parameters, return type and example
---
## Method: messages.deleteMessages  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|Array of [int](../types/int.md) | Yes|


### Return type: [Vector\_of\_int](../types/int.md)

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

$Vector_of_int = $MadelineProto->messages->deleteMessages(['id' => [int], ]);
```

Or, if you're into Lua:

```
Vector_of_int = messages.deleteMessages({id={int}, })
```

