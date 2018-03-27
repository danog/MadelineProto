---
title: contacts.getBlocked
description: Get blocked users
---
## Method: contacts.getBlocked  
[Back to methods index](index.md)


Get blocked users

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|offset|[int](../types/int.md) | Yes|Initially 0, then set to the number of blocked contacts previously fetched|
|limit|[int](../types/int.md) | Yes|How many blocked contacts to fetch|


### Return type: [contacts\_Blocked](../types/contacts_Blocked.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$contacts_Blocked = $MadelineProto->contacts->getBlocked(['offset' => int, 'limit' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.getBlocked`

Parameters:

offset - Json encoded int

limit - Json encoded int




Or, if you're into Lua:

```
contacts_Blocked = contacts.getBlocked({offset=int, limit=int, })
```

