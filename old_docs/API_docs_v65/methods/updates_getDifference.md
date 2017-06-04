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
|pts\_total\_limit|[int](../types/int.md) | Optional|
|date|[int](../types/int.md) | Yes|
|qts|[int](../types/int.md) | Yes|


### Return type: [updates\_Difference](../types/updates_Difference.md)

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

$updates_Difference = $MadelineProto->updates->getDifference(['pts' => int, 'pts_total_limit' => int, 'date' => int, 'qts' => int, ]);
```

Or, if you're into Lua:

```
updates_Difference = updates.getDifference({pts=int, pts_total_limit=int, date=int, qts=int, })
```

