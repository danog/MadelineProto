---
title: messages.search
description: messages.search parameters, return type and example
---
## Method: messages.search  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputPeer](../types/InputPeer.md) | Optional|
|q|[string](../types/string.md) | Yes|
|from\_id|[InputUser](../types/InputUser.md) | Optional|
|filter|[MessagesFilter](../types/MessagesFilter.md) | Yes|
|min\_date|[int](../types/int.md) | Yes|
|max\_date|[int](../types/int.md) | Yes|
|offset|[int](../types/int.md) | Yes|
|max\_id|[int](../types/int.md) | Yes|
|limit|[int](../types/int.md) | Yes|


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
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$messages_Messages = $MadelineProto->messages->search(['peer' => InputPeer, 'q' => 'string', 'from_id' => InputUser, 'filter' => MessagesFilter, 'min_date' => int, 'max_date' => int, 'offset' => int, 'max_id' => int, 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.search`

Parameters:

peer - Json encoded InputPeer

q - Json encoded string

from_id - Json encoded InputUser

filter - Json encoded MessagesFilter

min_date - Json encoded int

max_date - Json encoded int

offset - Json encoded int

max_id - Json encoded int

limit - Json encoded int




Or, if you're into Lua:

```
messages_Messages = messages.search({peer=InputPeer, q='string', from_id=InputUser, filter=MessagesFilter, min_date=int, max_date=int, offset=int, max_id=int, limit=int, })
```

