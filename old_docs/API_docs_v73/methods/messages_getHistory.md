---
title: messages.getHistory
description: Get previous messages of a group
---
## Method: messages.getHistory  
[Back to methods index](index.md)


Get previous messages of a group

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|The chat|
|offset\_id|[int](../types/int.md) | Yes|The last fetched message ID, initially 0|
|offset\_date|[int](../types/int.md) | Yes|The date of the last previously fetched message, initially 0|
|add\_offset|[int](../types/int.md) | Yes|Additional offset, can be 0|
|limit|[int](../types/int.md) | Yes|Number of messages to fetch|
|max\_id|[int](../types/int.md) | Yes|Maximum message ID to fetch|
|min\_id|[int](../types/int.md) | Yes|Minumum message ID to fetch|
|hash|[int](../types/int.md) | Yes|hash of old messages, $MadelineProto->gen_vector_hash($parsedMessageIds)|


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

$messages_Messages = $MadelineProto->messages->getHistory(['peer' => InputPeer, 'offset_id' => int, 'offset_date' => int, 'add_offset' => int, 'limit' => int, 'max_id' => int, 'min_id' => int, 'hash' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getHistory`

Parameters:

peer - Json encoded InputPeer

offset_id - Json encoded int

offset_date - Json encoded int

add_offset - Json encoded int

limit - Json encoded int

max_id - Json encoded int

min_id - Json encoded int

hash - Json encoded int




Or, if you're into Lua:

```
messages_Messages = messages.getHistory({peer=InputPeer, offset_id=int, offset_date=int, add_offset=int, limit=int, max_id=int, min_id=int, hash=int, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|CHAT_ID_INVALID|The provided chat id is invalid|
|PEER_ID_INVALID|The provided peer id is invalid|
|AUTH_KEY_PERM_EMPTY|The temporary auth key must be binded to the permanent auth key to use these methods.|
|Timeout|A timeout occurred while fetching data from the bot|


