---
title: help_getInviteText
description: help_getInviteText parameters, return type and example
---
## Method: help\_getInviteText  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|lang\_code|[string](../types/string.md) | Required|


### Return type: [help\_InviteText](../types/help_InviteText.md)

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

$help_InviteText = $MadelineProto->help->getInviteText(['lang_code' => string, ]);
```