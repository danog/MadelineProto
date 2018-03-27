---
title: channels.deleteHistory
description: Delete the history of a supergroup/channel
---
## Method: channels.deleteHistory  
[Back to methods index](index.md)


Delete the history of a supergroup/channel

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|The channel/supergroup|
|max\_id|[int](../types/int.md) | Yes|Maximum message ID to delete|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Bool = $MadelineProto->channels->deleteHistory(['channel' => InputChannel, 'max_id' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.deleteHistory
* params - `{"channel": InputChannel, "max_id": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.deleteHistory`

Parameters:

channel - Json encoded InputChannel

max_id - Json encoded int




Or, if you're into Lua:

```
Bool = channels.deleteHistory({channel=InputChannel, max_id=int, })
```

