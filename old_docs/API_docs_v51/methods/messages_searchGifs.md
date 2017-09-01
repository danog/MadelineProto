---
title: messages.searchGifs
description: messages.searchGifs parameters, return type and example
---
## Method: messages.searchGifs  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|q|[string](../types/string.md) | Yes|
|offset|[int](../types/int.md) | Yes|


### Return type: [messages\_FoundGifs](../types/messages_FoundGifs.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|SEARCH_QUERY_EMPTY|The search query is empty|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$messages_FoundGifs = $MadelineProto->messages->searchGifs(['q' => 'string', 'offset' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.searchGifs`

Parameters:

q - Json encoded string

offset - Json encoded int




Or, if you're into Lua:

```
messages_FoundGifs = messages.searchGifs({q='string', offset=int, })
```

