---
title: contacts.getSuggested
description: contacts.getSuggested parameters, return type and example
---
## Method: contacts.getSuggested  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|limit|[int](../types/int.md) | Required|


### Return type: [contacts\_Suggested](../types/contacts_Suggested.md)

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

$contacts_Suggested = $MadelineProto->contacts->getSuggested(['limit' => int, ]);
```