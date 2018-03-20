---
title: contacts.getContacts
description: Get info about a certain contact
---
## Method: contacts.getContacts  
[Back to methods index](index.md)


Get info about a certain contact

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|hash|[CLICK ME int](../types/int.md) | Yes|$ids is the list ids of previously fetched contacts, $hash = $MadelineProto->gen_vector_hash($ids);|


### Return type: [contacts\_Contacts](../types/contacts_Contacts.md)

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

$contacts_Contacts = $MadelineProto->contacts->getContacts(['hash' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.getContacts`

Parameters:

hash - Json encoded int




Or, if you're into Lua:

```
contacts_Contacts = contacts.getContacts({hash=int, })
```

