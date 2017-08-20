---
title: searchContacts
description: Searches for specified query in the first name, last name and username of the known user contacts
---
## Method: searchContacts  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Searches for specified query in the first name, last name and username of the known user contacts

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|query|[string](../types/string.md) | Yes|Query to search for, can be empty to return all contacts|
|limit|[int](../types/int.md) | Yes|Maximum number of users to be returned|


### Return type: [Users](../types/Users.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Users = $MadelineProto->searchContacts(['query' => 'string', 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - searchContacts
* params - `{"query": "string", "limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/searchContacts`

Parameters:

query - Json encoded string

limit - Json encoded int




Or, if you're into Lua:

```
Users = searchContacts({query='string', limit=int, })
```

