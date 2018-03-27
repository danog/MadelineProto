---
title: messages.getImportantHistory
description: Get important message history
---
## Method: messages.getImportantHistory  
[Back to methods index](index.md)


Get important message history

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|Peer|
|max\_id|[int](../types/int.md) | Yes|Maximum message ID to fetch|
|min\_id|[int](../types/int.md) | Yes|Minumum message ID to fetch|
|limit|[int](../types/int.md) | Yes|Number of results to fetch|


### Return type: [messages\_Messages](../types/messages_Messages.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$messages_Messages = $MadelineProto->messages->getImportantHistory(['peer' => InputPeer, 'max_id' => int, 'min_id' => int, 'limit' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.getImportantHistory
* params - `{"peer": InputPeer, "max_id": int, "min_id": int, "limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getImportantHistory`

Parameters:

peer - Json encoded InputPeer

max_id - Json encoded int

min_id - Json encoded int

limit - Json encoded int




Or, if you're into Lua:

```
messages_Messages = messages.getImportantHistory({peer=InputPeer, max_id=int, min_id=int, limit=int, })
```

