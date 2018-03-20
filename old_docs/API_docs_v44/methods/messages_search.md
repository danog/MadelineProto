---
title: messages.search
description: messages.search parameters, return type and example
---
## Method: messages.search  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|important\_only|[CLICK ME Bool](../types/Bool.md) | Optional|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|
|q|[CLICK ME string](../types/string.md) | Yes|
|filter|[CLICK ME MessagesFilter](../types/MessagesFilter.md) | Yes|
|min\_date|[CLICK ME int](../types/int.md) | Yes|
|max\_date|[CLICK ME int](../types/int.md) | Yes|
|offset|[CLICK ME int](../types/int.md) | Yes|
|max\_id|[CLICK ME int](../types/int.md) | Yes|
|limit|[CLICK ME int](../types/int.md) | Yes|


### Return type: [messages\_Messages](../types/messages_Messages.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|INPUT_CONSTRUCTOR_INVALID|The provided constructor is invalid|
|INPUT_USER_DEACTIVATED|The specified user was deleted|
|PEER_ID_INVALID|The provided peer id is invalid|
|PEER_ID_NOT_SUPPORTED|The provided peer ID is not supported|
|SEARCH_QUERY_EMPTY|The search query is empty|
|USER_ID_INVALID|The provided user ID is invalid|


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

$messages_Messages = $MadelineProto->messages->search(['important_only' => Bool, 'peer' => InputPeer, 'q' => 'string', 'filter' => MessagesFilter, 'min_date' => int, 'max_date' => int, 'offset' => int, 'max_id' => int, 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.search`

Parameters:

important_only - Json encoded Bool

peer - Json encoded InputPeer

q - Json encoded string

filter - Json encoded MessagesFilter

min_date - Json encoded int

max_date - Json encoded int

offset - Json encoded int

max_id - Json encoded int

limit - Json encoded int




Or, if you're into Lua:

```
messages_Messages = messages.search({important_only=Bool, peer=InputPeer, q='string', filter=MessagesFilter, min_date=int, max_date=int, offset=int, max_id=int, limit=int, })
```

