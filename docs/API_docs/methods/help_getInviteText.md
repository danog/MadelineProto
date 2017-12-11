---
title: help.getInviteText
description: help.getInviteText parameters, return type and example
---
## Method: help.getInviteText  
[Back to methods index](index.md)




### Return type: [help\_InviteText](../types/help_InviteText.md)

### Can bots use this method: **NO**


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$help_InviteText = $MadelineProto->help->getInviteText();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/help.getInviteText`

Parameters:




Or, if you're into Lua:

```
help_InviteText = help.getInviteText({})
```

