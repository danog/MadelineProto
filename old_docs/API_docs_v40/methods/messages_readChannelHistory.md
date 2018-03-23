---
title: messages.readChannelHistory
description: Mark channel/supergroup history as read
---
## Method: messages.readChannelHistory  
[Back to methods index](index.md)


Mark channel/supergroup history as read

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|The channel/supergruop|
|max\_id|[CLICK ME int](../types/int.md) | Yes|Maximum message ID to mark as read|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


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

$Bool = $MadelineProto->messages->readChannelHistory(['peer' => InputPeer, 'max_id' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.readChannelHistory
* params - `{"peer": InputPeer, "max_id": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.readChannelHistory`

Parameters:

peer - Json encoded InputPeer

max_id - Json encoded int




Or, if you're into Lua:

```
Bool = messages.readChannelHistory({peer=InputPeer, max_id=int, })
```

