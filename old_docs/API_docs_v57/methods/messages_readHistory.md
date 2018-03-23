---
title: messages.readHistory
description: Mark messages as read
---
## Method: messages.readHistory  
[Back to methods index](index.md)


Mark messages as read

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|Where to mark messages as read|
|max\_id|[CLICK ME int](../types/int.md) | Yes|Maximum message ID to mark as read|


### Return type: [messages\_AffectedMessages](../types/messages_AffectedMessages.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PEER_ID_INVALID|The provided peer id is invalid|
|Timeout|A timeout occurred while fetching data from the bot|


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

$messages_AffectedMessages = $MadelineProto->messages->readHistory(['peer' => InputPeer, 'max_id' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.readHistory`

Parameters:

peer - Json encoded InputPeer

max_id - Json encoded int




Or, if you're into Lua:

```
messages_AffectedMessages = messages.readHistory({peer=InputPeer, max_id=int, })
```

