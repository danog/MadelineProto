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
|hash|[string](../types/string.md) | Yes|Previously fetched stickers|


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

$messages_AllStickers = $MadelineProto->messages->getAllStickers(['hash' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getAllStickers`

Parameters:

hash - Json encoded string




Or, if you're into Lua:

```
messages_AllStickers = messages.getAllStickers({hash='string', })
```

