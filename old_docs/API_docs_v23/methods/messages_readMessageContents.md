---
title: messages.readMessageContents
description: messages.readMessageContents parameters, return type and example
---
## Method: messages.readMessageContents  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|Array of [int](../types/int.md) | Yes|


### Return type: [Vector\_of\_int](../types/int.md)

### Can bots use this method: **NO**


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Vector_of_int = $MadelineProto->messages->readMessageContents(['id' => [int], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.readMessageContents`

Parameters:

id - Json encoded  array of int




Or, if you're into Lua:

```
Vector_of_int = messages.readMessageContents({id={int}, })
```

