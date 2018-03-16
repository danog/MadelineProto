---
title: contacts.getBlocked
description: contacts.getBlocked parameters, return type and example
---
## Method: contacts.getBlocked  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|offset|[int](../types/int.md) | Yes|
|limit|[int](../types/int.md) | Yes|


### Return type: [contacts\_Blocked](../types/contacts_Blocked.md)

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

$contacts_Blocked = $MadelineProto->contacts->getBlocked(['offset' => int, 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.getBlocked`

Parameters:

offset - Json encoded int

limit - Json encoded int




Or, if you're into Lua:

```
contacts_Blocked = contacts.getBlocked({offset=int, limit=int, })
```

