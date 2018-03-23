---
title: messages.sendScreenshotNotification
description: Send screenshot notification
---
## Method: messages.sendScreenshotNotification  
[Back to methods index](index.md)


Send screenshot notification

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|Where to send the notification|
|reply\_to\_msg\_id|[CLICK ME int](../types/int.md) | Yes|Reply to message by ID|


### Return type: [Updates](../types/Updates.md)

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

$Updates = $MadelineProto->messages->sendScreenshotNotification(['peer' => InputPeer, 'reply_to_msg_id' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.sendScreenshotNotification`

Parameters:

peer - Json encoded InputPeer

reply_to_msg_id - Json encoded int




Or, if you're into Lua:

```
Updates = messages.sendScreenshotNotification({peer=InputPeer, reply_to_msg_id=int, })
```

