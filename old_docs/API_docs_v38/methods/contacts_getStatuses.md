---
title: contacts.getStatuses
description: contacts.getStatuses parameters, return type and example
---
## Method: contacts.getStatuses  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|


### Return type: [Vector\_of\_ContactStatus](../types/ContactStatus.md)

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

$Vector_of_ContactStatus = $MadelineProto->contacts->getStatuses();
```

Or, if you're into Lua:

```
Vector_of_ContactStatus = contacts.getStatuses({})
```

