---
title: messages.faveSticker
description: messages.faveSticker parameters, return type and example
---
## Method: messages.faveSticker  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[MessageMedia, Update, Message or InputDocument](../types/InputDocument.md) | Optional|
|unfave|[CLICK ME Bool](../types/Bool.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


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

$Bool = $MadelineProto->messages->faveSticker(['id' => InputDocument, 'unfave' => Bool, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.faveSticker
* params - `{"id": InputDocument, "unfave": Bool, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.faveSticker`

Parameters:

id - Json encoded InputDocument

unfave - Json encoded Bool




Or, if you're into Lua:

```
Bool = messages.faveSticker({id=InputDocument, unfave=Bool, })
```

