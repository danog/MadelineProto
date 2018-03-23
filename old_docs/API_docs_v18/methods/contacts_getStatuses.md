---
title: contacts.getStatuses
description: Get online status of all users
---
## Method: contacts.getStatuses  
[Back to methods index](index.md)


Get online status of all users



### Return type: [Vector\_of\_ContactStatus](../types/ContactStatus.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Vector_of_ContactStatus = $MadelineProto->contacts->getStatuses();
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.getStatuses`

Parameters:




Or, if you're into Lua:

```
Vector_of_ContactStatus = contacts.getStatuses({})
```

