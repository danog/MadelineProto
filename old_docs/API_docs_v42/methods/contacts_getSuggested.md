---
title: contacts.getSuggested
description: Get suggested contacts
---
## Method: contacts.getSuggested  
[Back to methods index](index.md)


Get suggested contacts

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|limit|[int](../types/int.md) | Yes|Number of results to return|


### Return type: [contacts\_Suggested](../types/contacts_Suggested.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$contacts_Suggested = $MadelineProto->contacts->getSuggested(['limit' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - contacts.getSuggested
* params - `{"limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.getSuggested`

Parameters:

limit - Json encoded int




Or, if you're into Lua:

```
contacts_Suggested = contacts.getSuggested({limit=int, })
```

