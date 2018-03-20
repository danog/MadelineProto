---
title: messages.getArchivedStickers
description: messages.getArchivedStickers parameters, return type and example
---
## Method: messages.getArchivedStickers  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|masks|[Bool](../types/Bool.md) | Optional|
|offset\_id|[long](../types/long.md) | Yes|
|limit|[int](../types/int.md) | Yes|


### Return type: [messages\_ArchivedStickers](../types/messages_ArchivedStickers.md)

### Can bots use this method: **NO**


### Example:


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

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



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

