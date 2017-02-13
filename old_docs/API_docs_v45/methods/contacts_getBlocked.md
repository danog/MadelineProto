---
title: contacts.getBlocked
description: contacts.getBlocked parameters, return type and example
---
## Method: contacts.getBlocked  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|offset|[int](../types/int.md) | Required|
|limit|[int](../types/int.md) | Required|


### Return type: [contacts\_Blocked](../types/contacts_Blocked.md)

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

$contacts_Blocked = $MadelineProto->contacts->getBlocked(['offset' => int, 'limit' => int, ]);
```
