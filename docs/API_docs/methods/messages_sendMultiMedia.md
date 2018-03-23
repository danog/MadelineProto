---
title: messages.sendMultiMedia
description: Send an album
---
## Method: messages.sendMultiMedia  
[Back to methods index](index.md)


Send an album

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|silent|[CLICK ME Bool](../types/Bool.md) | Optional|Disable notifications?|
|background|[CLICK ME Bool](../types/Bool.md) | Optional|Disable background notification?|
|clear\_draft|[CLICK ME Bool](../types/Bool.md) | Optional|Clear draft?|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|Where to send the album|
|reply\_to\_msg\_id|[CLICK ME int](../types/int.md) | Optional|Reply to message by ID|
|multi\_media|Array of [CLICK ME InputSingleMedia](../types/InputSingleMedia.md) | Yes|The album|


### Return type: [Updates](../types/Updates.md)

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

$Updates = $MadelineProto->messages->sendMultiMedia(['silent' => Bool, 'background' => Bool, 'clear_draft' => Bool, 'peer' => InputPeer, 'reply_to_msg_id' => int, 'multi_media' => [InputSingleMedia, InputSingleMedia], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.sendMultiMedia
* params - `{"silent": Bool, "background": Bool, "clear_draft": Bool, "peer": InputPeer, "reply_to_msg_id": int, "multi_media": [InputSingleMedia], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.sendMultiMedia`

Parameters:

silent - Json encoded Bool

background - Json encoded Bool

clear_draft - Json encoded Bool

peer - Json encoded InputPeer

reply_to_msg_id - Json encoded int

multi_media - Json encoded  array of InputSingleMedia




Or, if you're into Lua:

```
Updates = messages.sendMultiMedia({silent=Bool, background=Bool, clear_draft=Bool, peer=InputPeer, reply_to_msg_id=int, multi_media={InputSingleMedia}, })
```

