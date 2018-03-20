---
title: messages.saveRecentSticker
description: messages.saveRecentSticker parameters, return type and example
---
## Method: messages.saveRecentSticker  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[MessageMedia, Update, Message or InputDocument](../types/InputDocument.md) | Optional|
|unsave|[CLICK ME Bool](../types/Bool.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|STICKER_ID_INVALID|The provided sticker ID is invalid|


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

$Bool = $MadelineProto->messages->saveRecentSticker(['id' => InputDocument, 'unsave' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.saveRecentSticker`

Parameters:

id - Json encoded InputDocument

unsave - Json encoded Bool




Or, if you're into Lua:

```
Bool = messages.saveRecentSticker({id=InputDocument, unsave=Bool, })
```

