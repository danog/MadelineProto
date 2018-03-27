---
title: contacts.deleteContact
description: Delete a contact
---
## Method: contacts.deleteContact  
[Back to methods index](index.md)


Delete a contact

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|The contact to delete|


### Return type: [contacts\_Link](../types/contacts_Link.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$contacts_Link = $MadelineProto->contacts->deleteContact(['id' => InputUser, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.deleteContact`

Parameters:

id - Json encoded InputUser




Or, if you're into Lua:

```
contacts_Link = contacts.deleteContact({id=InputUser, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CONTACT_ID_INVALID|The provided contact ID is invalid|


