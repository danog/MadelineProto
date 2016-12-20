---
title: updates_getDifference
---
## Method: updates\_getDifference  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|pts|[int](../types/int.md) | Required|
|date|[int](../types/int.md) | Required|
|qts|[int](../types/int.md) | Required|


### Return type: [updates\_Difference](../types/updates_Difference.md)

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

$updates_Difference = $MadelineProto->updates_getDifference(['pts' => int, 'date' => int, 'qts' => int, ]);
```