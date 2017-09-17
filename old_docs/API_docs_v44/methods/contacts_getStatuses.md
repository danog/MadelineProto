---
title: contacts.getStatuses
description: contacts.getStatuses parameters, return type and example
---
## Method: contacts.getStatuses  
[Back to methods index](index.md)




### Return type: [Vector\_of\_ContactStatus](../types/ContactStatus.md)

### Can bots use this method: **NO**


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Vector_of_ContactStatus = $MadelineProto->contacts->getStatuses();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.getStatuses`

Parameters:




Or, if you're into Lua:

```
Vector_of_ContactStatus = contacts.getStatuses({})
```

