---
title: messages.deleteHistory
description: Delete chat history
---
## Method: messages.deleteHistory  
[Back to methods index](index.md)


Delete chat history

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|Where to clear it|
|offset|[CLICK ME int](../types/int.md) | Yes|Offset|


### Return type: [messages\_AffectedHistory](../types/messages_AffectedHistory.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PEER_ID_INVALID|The provided peer id is invalid|


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

$messages_AffectedHistory = $MadelineProto->messages->deleteHistory(['peer' => InputPeer, 'offset' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.deleteHistory`

Parameters:

peer - Json encoded InputPeer

offset - Json encoded int




Or, if you're into Lua:

```
messages_AffectedHistory = messages.deleteHistory({peer=InputPeer, offset=int, })
```

