---
title: contacts.search
description: Search contacts
---
## Method: contacts.search  
[Back to methods index](index.md)


Search contacts

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|q|[string](../types/string.md) | Yes|The search query|
|limit|[int](../types/int.md) | Yes|How many results should be returned|


### Return type: [contacts\_Found](../types/contacts_Found.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$contacts_Found = $MadelineProto->contacts->search(['q' => 'string', 'limit' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.search`

Parameters:

q - Json encoded string

limit - Json encoded int




Or, if you're into Lua:

```
contacts_Found = contacts.search({q='string', limit=int, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|QUERY_TOO_SHORT|The query string is too short|
|SEARCH_QUERY_EMPTY|The search query is empty|
|Timeout|A timeout occurred while fetching data from the bot|


