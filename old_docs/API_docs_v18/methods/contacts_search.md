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
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

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

