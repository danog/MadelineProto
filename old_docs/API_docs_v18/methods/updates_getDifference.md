---
title: updates.getDifference
description: updates.getDifference parameters, return type and example
---
## Method: updates.getDifference  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|pts|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|
|qts|[int](../types/int.md) | Yes|


### Return type: [updates\_Difference](../types/updates_Difference.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
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

$updates_Difference = $MadelineProto->updates->getDifference(['pts' => int, 'date' => int, 'qts' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - updates.getDifference
* params - `{"pts": int, "date": int, "qts": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/updates.getDifference`

Parameters:

pts - Json encoded int
date - Json encoded int
qts - Json encoded int



Or, if you're into Lua:

```
updates_Difference = updates.getDifference({pts=int, date=int, qts=int, })
```

