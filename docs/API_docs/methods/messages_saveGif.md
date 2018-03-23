---
title: messages.saveGif
description: Save a GIF
---
## Method: messages.saveGif  
[Back to methods index](index.md)


Save a GIF

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[MessageMedia, Update, Message or InputDocument](../types/InputDocument.md) | Optional|The GIF to save|
|unsave|[CLICK ME Bool](../types/Bool.md) | Yes|Remove the gif?|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|GIF_ID_INVALID|The provided GIF ID is invalid|


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

$Bool = $MadelineProto->messages->saveGif(['id' => InputDocument, 'unsave' => Bool, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.saveGif`

Parameters:

id - Json encoded InputDocument

unsave - Json encoded Bool




Or, if you're into Lua:

```
Bool = messages.saveGif({id=InputDocument, unsave=Bool, })
```

