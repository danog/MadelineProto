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
|contacts|Array of [CLICK ME InputContact](../types/InputContact.md) | Yes|The numbers to import|


### Return type: [contacts\_ImportedContacts](../types/contacts_ImportedContacts.md)

### Can bots use this method: **NO**


### Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

// !!! This API id/API hash combination will not work !!!
// !!! You must get your own @ my.telegram.org !!!
$api_id = 0;
$api_hash = '';

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['app_info' => ['api_id' => $api_id, 'api_hash' => $api_hash]]);
$MadelineProto->start();

$contacts_ImportedContacts = $MadelineProto->contacts->importContacts(['contacts' => [InputContact, InputContact], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.importContacts`

Parameters:

contacts - Json encoded  array of InputContact




Or, if you're into Lua:

```
contacts_ImportedContacts = contacts.importContacts({contacts={InputContact}, })
```

