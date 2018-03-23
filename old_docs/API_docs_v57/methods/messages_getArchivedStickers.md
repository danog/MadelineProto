---
title: messages.getArchivedStickers
description: Get all archived stickers
---
## Method: messages.getArchivedStickers  
[Back to methods index](index.md)


Get all archived stickers

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|masks|[CLICK ME Bool](../types/Bool.md) | Optional|Get masks?|
|offset\_id|[CLICK ME long](../types/long.md) | Yes|Sticker ID offset|
|limit|[CLICK ME int](../types/int.md) | Yes|Number of stickers to fetch|


### Return type: [messages\_ArchivedStickers](../types/messages_ArchivedStickers.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

// !!! This API id/API hash combination will not work !!!
// !!! You must get your own @ my.telegram.org !!!
$api_id = 0;
$api_hash = '';

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['app_info' => ['api_id' => $api_id, 'api_hash' => $api_hash]]);
$MadelineProto->start();

$messages_ArchivedStickers = $MadelineProto->messages->getArchivedStickers(['masks' => Bool, 'offset_id' => long, 'limit' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getArchivedStickers`

Parameters:

masks - Json encoded Bool

offset_id - Json encoded long

limit - Json encoded int




Or, if you're into Lua:

```
messages_ArchivedStickers = messages.getArchivedStickers({masks=Bool, offset_id=long, limit=int, })
```

