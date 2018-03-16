---
title: geochats.editChatTitle
description: geochats.editChatTitle parameters, return type and example
---
## Method: geochats.editChatTitle  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputGeoChat](../types/InputGeoChat.md) | Yes|
|title|[string](../types/string.md) | Yes|
|address|[string](../types/string.md) | Yes|


### Return type: [geochats\_StatedMessage](../types/geochats_StatedMessage.md)

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

$geochats_StatedMessage = $MadelineProto->geochats->editChatTitle(['peer' => InputGeoChat, 'title' => 'string', 'address' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

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

