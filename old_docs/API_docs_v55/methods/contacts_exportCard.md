---
title: contacts.exportCard
description: contacts.exportCard parameters, return type and example
---
## Method: contacts.exportCard  
[Back to methods index](index.md)




### Return type: [Vector\_of\_int](../types/int.md)

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

$Vector_of_int = $MadelineProto->contacts->exportCard();
```