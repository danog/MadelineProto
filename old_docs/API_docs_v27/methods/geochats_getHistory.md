---
title: geochats.getHistory
description: Get geochat history
---
## Method: geochats.getHistory  
[Back to methods index](index.md)


Get geochat history

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[InputGeoChat](../types/InputGeoChat.md) | Yes|The geochat|
|offset|[int](../types/int.md) | Yes|Offset|
|max\_id|[int](../types/int.md) | Yes|Maximum message ID|
|limit|[int](../types/int.md) | Yes|Number of results to return|


### Return type: [geochats\_Messages](../types/geochats_Messages.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$geochats_Messages = $MadelineProto->geochats->getHistory(['peer' => InputGeoChat, 'offset' => int, 'max_id' => int, 'limit' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - geochats.getHistory
* params - `{"peer": InputGeoChat, "offset": int, "max_id": int, "limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/geochats.getHistory`

Parameters:

peer - Json encoded InputGeoChat

offset - Json encoded int

max_id - Json encoded int

limit - Json encoded int




Or, if you're into Lua:

```
geochats_Messages = geochats.getHistory({peer=InputGeoChat, offset=int, max_id=int, limit=int, })
```

