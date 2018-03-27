---
title: geochats.editChatTitle
description: Edit geochat title
---
## Method: geochats.editChatTitle  
[Back to methods index](index.md)


Edit geochat title

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[InputGeoChat](../types/InputGeoChat.md) | Yes|The geochat|
|title|[string](../types/string.md) | Yes|The new title|
|address|[string](../types/string.md) | Yes|The new address|


### Return type: [geochats\_StatedMessage](../types/geochats_StatedMessage.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$geochats_StatedMessage = $MadelineProto->geochats->editChatTitle(['peer' => InputGeoChat, 'title' => 'string', 'address' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - geochats.editChatTitle
* params - `{"peer": InputGeoChat, "title": "string", "address": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/geochats.editChatTitle`

Parameters:

peer - Json encoded InputGeoChat

title - Json encoded string

address - Json encoded string




Or, if you're into Lua:

```
geochats_StatedMessage = geochats.editChatTitle({peer=InputGeoChat, title='string', address='string', })
```

