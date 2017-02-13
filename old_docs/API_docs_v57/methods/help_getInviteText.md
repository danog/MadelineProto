---
title: help.getInviteText
description: help.getInviteText parameters, return type and example
---
## Method: help.getInviteText  
[Back to methods index](index.md)




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

$help_InviteText = $MadelineProto->help->getInviteText();
```
