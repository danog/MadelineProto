---
title: help.getSupport
description: help.getSupport parameters, return type and example
---
## Method: help.getSupport  
[Back to methods index](index.md)




### Return type: [help\_Support](../types/help_Support.md)

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

$help_Support = $MadelineProto->help->getSupport();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/help.getSupport`

Parameters:




Or, if you're into Lua:

```
help_Support = help.getSupport({})
```

