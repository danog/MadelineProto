---
title: contacts.importContacts
description: Add phone number as contact
---
## Method: contacts.importContacts  
[Back to methods index](index.md)


Add phone number as contact

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|contacts|Array of [InputContact](../types/InputContact.md) | Yes|The numbers to import|
|replace|[Bool](../types/Bool.md) | Yes|Replace contacts?|


### Return type: [contacts\_ImportedContacts](../types/contacts_ImportedContacts.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$contacts_ImportedContacts = $MadelineProto->contacts->importContacts(['contacts' => [InputContact, InputContact], 'replace' => Bool, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.importContacts`

Parameters:

contacts - Json encoded  array of InputContact

replace - Json encoded Bool




Or, if you're into Lua:

```
contacts_ImportedContacts = contacts.importContacts({contacts={InputContact}, replace=Bool, })
```

