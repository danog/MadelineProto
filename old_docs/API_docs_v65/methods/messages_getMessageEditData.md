---
title: messages.getMessageEditData
description: Check if about to edit a message or a media caption
---
## Method: messages.getMessageEditData  
[Back to methods index](index.md)


Check if about to edit a message or a media caption

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|The chat|
|id|[CLICK ME int](../types/int.md) | Yes|The message ID|


### Return type: [messages\_MessageEditData](../types/messages_MessageEditData.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PEER_ID_INVALID|The provided peer id is invalid|
|MESSAGE_AUTHOR_REQUIRED|Message author required|


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

$messages_MessageEditData = $MadelineProto->messages->getMessageEditData(['peer' => InputPeer, 'id' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getMessageEditData`

Parameters:

peer - Json encoded InputPeer

id - Json encoded int




Or, if you're into Lua:

```
messages_MessageEditData = messages.getMessageEditData({peer=InputPeer, id=int, })
```

