---
title: contacts.importContacts
description: contacts.importContacts parameters, return type and example
---
## Method: contacts.importContacts  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|contacts|Array of [InputContact](../types/InputContact.md) | Yes|
|replace|[Bool](../types/Bool.md) | Yes|


### Return type: [contacts\_ImportedContacts](../types/contacts_ImportedContacts.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
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

$contacts_ImportedContacts = $MadelineProto->contacts->importContacts(['contacts' => [InputContact], 'replace' => Bool, ]);
```

Or, if you're into Lua:

```
contacts_ImportedContacts = contacts.importContacts({contacts={InputContact}, replace=Bool, })
```

