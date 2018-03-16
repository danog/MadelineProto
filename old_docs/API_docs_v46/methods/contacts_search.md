---
title: contacts.search
description: contacts.search parameters, return type and example
---
## Method: contacts.search  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|q|[string](../types/string.md) | Yes|
|limit|[int](../types/int.md) | Yes|


### Return type: [contacts\_Found](../types/contacts_Found.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|QUERY_TOO_SHORT|The query string is too short|
|SEARCH_QUERY_EMPTY|The search query is empty|
|Timeout|A timeout occurred while fetching data from the bot|


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

$contacts_Found = $MadelineProto->contacts->search(['q' => 'string', 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.search`

Parameters:

q - Json encoded string

limit - Json encoded int




Or, if you're into Lua:

```
contacts_Found = contacts.search({q='string', limit=int, })
```

