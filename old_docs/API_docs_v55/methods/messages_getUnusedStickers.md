---
title: messages.getUnusedStickers
description: messages.getUnusedStickers parameters, return type and example
---
## Method: messages.getUnusedStickers  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|limit|[int](../types/int.md) | Yes|


### Return type: [Vector\_of\_StickerSetCovered](../types/StickerSetCovered.md)

### Can bots use this method: **YES**


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

$Vector_of_StickerSetCovered = $MadelineProto->messages->getUnusedStickers(['limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.getUnusedStickers
* params - `{"limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getUnusedStickers`

Parameters:

limit - Json encoded int




Or, if you're into Lua:

```
Vector_of_StickerSetCovered = messages.getUnusedStickers({limit=int, })
```

