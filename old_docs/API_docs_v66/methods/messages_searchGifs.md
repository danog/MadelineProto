---
title: messages.searchGifs
description: Search gifs
---
## Method: messages.searchGifs  
[Back to methods index](index.md)


Search gifs

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|q|[string](../types/string.md) | Yes|The search query|
|offset|[int](../types/int.md) | Yes|The offset |


### Return type: [messages\_FoundGifs](../types/messages_FoundGifs.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$messages_FoundGifs = $MadelineProto->messages->searchGifs(['q' => 'string', 'offset' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.searchGifs`

Parameters:

q - Json encoded string

offset - Json encoded int




Or, if you're into Lua:

```
messages_FoundGifs = messages.searchGifs({q='string', offset=int, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|SEARCH_QUERY_EMPTY|The search query is empty|


