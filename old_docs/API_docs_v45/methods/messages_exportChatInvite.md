---
title: messages.exportChatInvite
description: messages.exportChatInvite parameters, return type and example
---
## Method: messages.exportChatInvite  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Optional|


### Return type: [ExportedChatInvite](../types/ExportedChatInvite.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_ID_INVALID|The provided chat id is invalid|


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

$ExportedChatInvite = $MadelineProto->messages->exportChatInvite(['chat_id' => InputPeer, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.exportChatInvite`

Parameters:

chat_id - Json encoded InputPeer




Or, if you're into Lua:

```
ExportedChatInvite = messages.exportChatInvite({chat_id=InputPeer, })
```

