---
title: messages.getRecentLocations
description: Get recent locations
---
## Method: messages.getRecentLocations  
[Back to methods index](index.md)


Get recent locations

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|The chat where to search locations|
|limit|[int](../types/int.md) | Yes|Number of results to return|


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

$messages_Messages = $MadelineProto->messages->getRecentLocations(['peer' => InputPeer, 'limit' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.getRecentLocations
* params - `{"peer": InputPeer, "limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getRecentLocations`

Parameters:

peer - Json encoded InputPeer

limit - Json encoded int




Or, if you're into Lua:

```
messages_Messages = messages.getRecentLocations({peer=InputPeer, limit=int, })
```

