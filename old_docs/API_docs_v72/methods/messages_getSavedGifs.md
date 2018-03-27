---
title: messages.getSavedGifs
description: Get saved gifs
---
## Method: messages.getSavedGifs  
[Back to methods index](index.md)


Get saved gifs

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|hash|[int](../types/int.md) | Yes|0 or $result['hash']|


### Return type: [messages\_SavedGifs](../types/messages_SavedGifs.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$messages_SavedGifs = $MadelineProto->messages->getSavedGifs(['hash' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getSavedGifs`

Parameters:

hash - Json encoded int




Or, if you're into Lua:

```
messages_SavedGifs = messages.getSavedGifs({hash=int, })
```

