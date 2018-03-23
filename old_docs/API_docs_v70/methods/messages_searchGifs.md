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
|q|[CLICK ME string](../types/string.md) | Yes|The search query|
|offset|[CLICK ME int](../types/int.md) | Yes|The offset |


### Return type: [messages\_FoundGifs](../types/messages_FoundGifs.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|SEARCH_QUERY_EMPTY|The search query is empty|


### MadelineProto Example:


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

