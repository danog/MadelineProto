---
title: messages.saveRecentSticker
description: Add a sticker to recent stickers
---
## Method: messages.saveRecentSticker  
[Back to methods index](index.md)


Add a sticker to recent stickers

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[MessageMedia, Update, Message or InputDocument](../types/InputDocument.md) | Optional|The sticker|
|unsave|[CLICK ME Bool](../types/Bool.md) | Yes|Remove the sticker from recent stickers?|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|STICKER_ID_INVALID|The provided sticker ID is invalid|


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

$Bool = $MadelineProto->messages->saveRecentSticker(['id' => InputDocument, 'unsave' => Bool, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.saveRecentSticker`

Parameters:

id - Json encoded InputDocument

unsave - Json encoded Bool




Or, if you're into Lua:

```
Bool = messages.saveRecentSticker({id=InputDocument, unsave=Bool, })
```

