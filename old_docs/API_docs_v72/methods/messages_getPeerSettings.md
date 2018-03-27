---
title: messages.getPeerSettings
description: Get the settings of  apeer
---
## Method: messages.getPeerSettings  
[Back to methods index](index.md)


Get the settings of  apeer

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|The peer|


### Return type: [PeerSettings](../types/PeerSettings.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$PeerSettings = $MadelineProto->messages->getPeerSettings(['peer' => InputPeer, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getPeerSettings`

Parameters:

peer - Json encoded InputPeer




Or, if you're into Lua:

```
PeerSettings = messages.getPeerSettings({peer=InputPeer, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|PEER_ID_INVALID|The provided peer id is invalid|


