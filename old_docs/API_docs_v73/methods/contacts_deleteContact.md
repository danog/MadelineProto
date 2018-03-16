---
title: contacts.deleteContact
description: contacts.deleteContact parameters, return type and example
---
## Method: contacts.deleteContact  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[InputUser](../types/InputUser.md) | Optional|


### Return type: [contacts\_Link](../types/contacts_Link.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CONTACT_ID_INVALID|The provided contact ID is invalid|


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

$contacts_Link = $MadelineProto->contacts->deleteContact(['id' => InputUser, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.deleteContact`

Parameters:

id - Json encoded InputUser




Or, if you're into Lua:

```
contacts_Link = contacts.deleteContact({id=InputUser, })
```

