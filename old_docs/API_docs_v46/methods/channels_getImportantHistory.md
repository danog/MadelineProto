---
title: channels.getImportantHistory
description: Get important channel/supergroup history
---
## Method: channels.getImportantHistory  
[Back to methods index](index.md)


Get important channel/supergroup history

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|The supergroup/channel|
|offset\_id|[int](../types/int.md) | Yes|Message ID offset|
|add\_offset|[int](../types/int.md) | Yes|Additional offset|
|limit|[int](../types/int.md) | Yes|Number of results to return|
|max\_id|[int](../types/int.md) | Yes|Maximum message ID|
|min\_id|[int](../types/int.md) | Yes|Minumum message ID|


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

$messages_Messages = $MadelineProto->channels->getImportantHistory(['channel' => InputChannel, 'offset_id' => int, 'add_offset' => int, 'limit' => int, 'max_id' => int, 'min_id' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.getImportantHistory
* params - `{"channel": InputChannel, "offset_id": int, "add_offset": int, "limit": int, "max_id": int, "min_id": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.getImportantHistory`

Parameters:

channel - Json encoded InputChannel

offset_id - Json encoded int

add_offset - Json encoded int

limit - Json encoded int

max_id - Json encoded int

min_id - Json encoded int




Or, if you're into Lua:

```
messages_Messages = channels.getImportantHistory({channel=InputChannel, offset_id=int, add_offset=int, limit=int, max_id=int, min_id=int, })
```

