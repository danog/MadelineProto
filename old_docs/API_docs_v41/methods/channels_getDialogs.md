---
title: channels.getDialogs
description: Get channel dialogs
---
## Method: channels.getDialogs  
[Back to methods index](index.md)


Get channel dialogs

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|offset|[int](../types/int.md) | Yes|Offset|
|limit|[int](../types/int.md) | Yes|Number of results to return|


### Return type: [messages\_Dialogs](../types/messages_Dialogs.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$messages_Dialogs = $MadelineProto->channels->getDialogs(['offset' => int, 'limit' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.getDialogs
* params - `{"offset": int, "limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.getDialogs`

Parameters:

offset - Json encoded int

limit - Json encoded int




Or, if you're into Lua:

```
messages_Dialogs = channels.getDialogs({offset=int, limit=int, })
```

