---
title: messages.getAllStickers
description: Get all stickerpacks
---
## Method: messages.getAllStickers  
[Back to methods index](index.md)


Get all stickerpacks

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|hash|[int](../types/int.md) | Yes|0 or $result['hash']|


### Return type: [messages\_AllStickers](../types/messages_AllStickers.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$messages_AllStickers = $MadelineProto->messages->getAllStickers(['hash' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getAllStickers`

Parameters:

hash - Json encoded int




Or, if you're into Lua:

```
messages_AllStickers = messages.getAllStickers({hash=int, })
```

