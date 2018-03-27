---
title: messages.search
description: Search peers or messages
---
## Method: messages.search  
[Back to methods index](index.md)


Search peers or messages

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|Where to search|
|q|[string](../types/string.md) | Yes|What to search|
|from\_id|[Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|Show only messages from a certain user?|
|filter|[MessagesFilter](../types/MessagesFilter.md) | Yes|Message filter|
|min\_date|[int](../types/int.md) | Yes|Minumum date of results to fetch|
|max\_date|[int](../types/int.md) | Yes|Maximum date of results to fetch|
|offset\_id|[int](../types/int.md) | Yes|Offset |
|add\_offset|[int](../types/int.md) | Yes|Additional offset, can be 0|
|limit|[int](../types/int.md) | Yes|Number of results to return|
|max\_id|[int](../types/int.md) | Yes|Maximum message id to return|
|min\_id|[int](../types/int.md) | Yes|Minumum message id to return|


### Return type: [messages\_Messages](../types/messages_Messages.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$messages_Messages = $MadelineProto->messages->search(['peer' => InputPeer, 'q' => 'string', 'from_id' => InputUser, 'filter' => MessagesFilter, 'min_date' => int, 'max_date' => int, 'offset_id' => int, 'add_offset' => int, 'limit' => int, 'max_id' => int, 'min_id' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.search`

Parameters:

peer - Json encoded InputPeer

q - Json encoded string

from_id - Json encoded InputUser

filter - Json encoded MessagesFilter

min_date - Json encoded int

max_date - Json encoded int

offset_id - Json encoded int

add_offset - Json encoded int

limit - Json encoded int

max_id - Json encoded int

min_id - Json encoded int




Or, if you're into Lua:

```
messages_Messages = messages.search({peer=InputPeer, q='string', from_id=InputUser, filter=MessagesFilter, min_date=int, max_date=int, offset_id=int, add_offset=int, limit=int, max_id=int, min_id=int, })
```

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


