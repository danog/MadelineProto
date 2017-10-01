---
title: contacts.importContacts
description: contacts.importContacts parameters, return type and example
---
## Method: contacts.importContacts  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|contacts|Array of [InputContact](../types/InputContact.md) | Yes|
|replace|[Bool](../types/Bool.md) | Yes|


### Return type: [contacts\_ImportedContacts](../types/contacts_ImportedContacts.md)

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

$contacts_ImportedContacts = $MadelineProto->contacts->importContacts(['contacts' => [InputContact], 'replace' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.importContacts`

Parameters:

contacts - Json encoded  array of InputContact

replace - Json encoded Bool




Or, if you're into Lua:

```
contacts_ImportedContacts = contacts.importContacts({contacts={InputContact}, replace=Bool, })
```

